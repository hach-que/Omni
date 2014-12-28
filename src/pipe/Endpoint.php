<?php

/**
 * The class which actually wraps a native system pipe, serializing and
 * deserializing data as needed.
 */
final class Endpoint extends Phobject {

  const FORMAT_PHP_SERIALIZATION = 'php-serialization';
  const FORMAT_LENGTH_PREFIXED_JSON = 'json-lp';
  const FORMAT_BYTE_STREAM = 'byte-stream';
  const FORMAT_NEWLINE_SEPARATED = 'newline-separated';
  const FORMAT_NULL_SEPARATED = 'null-separated';
  const FORMAT_USER_FRIENDLY = 'user-friendly';

  private $nativePipe;
  private $nativePipePending;
  private $writeFormat = self::FORMAT_PHP_SERIALIZATION;
  private $readFormat = self::FORMAT_PHP_SERIALIZATION;
  private $name = null;
  private $closable = true;
  private $userFriendlyFormatter = null;
  private $ownerPipe = null;
  private $ownerIndex = null;
  private $ownerType = null;

  public function __construct($pipe = null) {
    if ($pipe === null) {
      $this->nativePipe = null; /* Pipe is pending until startController */
      $this->nativePipePending = true;
    } else {
      $this->nativePipe = $pipe;
      $this->nativePipePending = false;
    }
  }
  
  private function isRemoted() {
    return $this->ownerPipe !== null &&
      $this->ownerIndex !== null &&
      $this->ownerType !== null;
  }
  
  private function remoteCall($function_name) {
    if (!$this->isRemoted()) {
      throw new Exception('remoteCall called on non-remoted endpoint');
    }
    
    $this->ownerPipe->dispatchControlEndpointCallEvent(
      $this->ownerIndex,
      $this->ownerType,
      $function_name,
      func_get_args());
  }
  
  public function setOwnerPipe(Pipe $pipe) {
    $this->ownerPipe = $pipe;
    return $this;
  }
  
  public function setOwnerIndex($index) {
    $this->ownerIndex = $index;
    return $this;
  }
  
  public function setOwnerType($type) {
    $this->ownerType = $type;
    return $this;
  }
  
  public function setName($name) {
    if ($this->isRemoted()) {
      $this->remoteCall(__FUNCTION__, $name);
    }
    $this->name = $name;
    return $this;
  }
  
  public function getName() {
    if ($this->name === null) {
      return '(unnamed endpoint)';
    }
    
    return $this->name;
  }
  
  public function setClosable($closable) {
    if ($this->isRemoted()) {
      $this->remoteCall(__FUNCTION__, $closable);
    }
    $this->closable = $closable;
    return $this;
  }
  
  public function getReadFD() {
    if ($this->nativePipePending) {
      throw new Exception('Attempted to call getReadFD on endpoint with non-started pipe');
    }
    
    $read = $this->nativePipe['read'];
    if ($read === null) {
      throw new Exception('Attempted to call getReadFD on write-only pipe');
    }
  
    return $read;
  }
  
  public function getWriteFD() {
    if ($this->nativePipePending) {
      throw new Exception('Attempted to call getReadFD on endpoint with non-started pipe');
    }
    
    $write = $this->nativePipe['write'];
    if ($write === null) {
      throw new Exception('Attempted to call getWriteFD on read-only pipe');
    }
  
    return $write;
  }
  
  public function setWriteFormat($format) {
    if ($this->isRemoted()) {
      $this->remoteCall(__FUNCTION__, $format);
    }
    $this->writeFormat = $format;
    return $this;
  }
  
  public function setReadFormat($format) {
    if ($this->isRemoted()) {
      $this->remoteCall(__FUNCTION__, $format);
    }
    $this->readFormat = $format;
    return $this;
  }
  
  public function getWriteFormat($format) {
    return $this->writeFormat;
  }
  
  public function getReadFormat($format) {
    return $this->readFormat;
  }
  
  public function instantiatePipe() {
    if ($this->nativePipePending) {
      $this->nativePipe = FileDescriptorManager::createPipe('endpoint read', 'endpoint write');
      $this->nativePipePending = false;
      omni_trace("created a pipe with read FD ".$this->getReadFD()." and write FD ".$this->getWriteFD());
    }
  }
  
  private function convertToStringIfNeeded($object) {
    try {
      assert_stringlike($object);
      return (string)$object;
    } catch (Exception $e) {
      return print_r($object, true);
    }
  }
  
  public function write($object) {
    if ($object instanceof Exception) {
      $object = $this->handleExceptionWritten($object);
    }
  
    $data = null;
    switch ($this->writeFormat) {
      case self::FORMAT_PHP_SERIALIZATION:
        $data = $this->lengthPrefix(serialize($object));
        break;
      case self::FORMAT_LENGTH_PREFIXED_JSON:
        $data = $this->lengthPrefix(json_encode($object));
        break;
      case self::FORMAT_BYTE_STREAM:
        $data = $this->convertToStringIfNeeded($object);
        break;
      case self::FORMAT_NEWLINE_SEPARATED:
        $data = trim($this->convertToStringIfNeeded($object), "\n")."\n";
        break;
      case self::FORMAT_NULL_SEPARATED:
        $data = trim($this->convertToStringIfNeeded($object), "\0")."\0";
        break;
      case self::FORMAT_USER_FRIENDLY:
        if ($this->userFriendlyFormatter === null) {
          $this->userFriendlyFormatter = new UserFriendlyFormatter();
        }
        $data = $this->userFriendlyFormatter->get($object);
        break;
    }
    
    FileDescriptorManager::write($this->getWriteFD(), $data);
  }
  
  /**
   * We modify and / or wrap exceptions to keep track of all of the
   * processes that it passes through.  This is because the exception
   * will be written by the outer-most instance of Omni, but may occur in
   * a nested process.
   */
  private function handleExceptionWritten(Exception $ex) {
    if (!($ex instanceof ProcessAwareException)) {
      $ex = new ProcessAwareException($ex);
    }
    
    $ex->addProcessTrace("written to endpoint from PID ".posix_getpid());
    return $ex;
  }
  
  private function lengthPrefix($data) {
    $length = strlen($data);
    $length_byte_1 = ($length >> 24) & 0xFF;
    $length_byte_2 = ($length >> 16) & 0xFF;
    $length_byte_3 = ($length >> 8) & 0xFF;
    $length_byte_4 = $length & 0xFF;
    return 
      chr($length_byte_1).
      chr($length_byte_2).
      chr($length_byte_3).
      chr($length_byte_4).
      $data;
  }
  
  public function read() {
    $fd = $this->getReadFD();
    
    omni_trace("called read for FD $fd");
    
    switch ($this->writeFormat) {
      case self::FORMAT_PHP_SERIALIZATION:
        FileDescriptorManager::setBlocking($fd, true);
        $data = $this->readLengthPrefixed($fd);
        return unserialize($data);
      case self::FORMAT_LENGTH_PREFIXED_JSON:
        FileDescriptorManager::setBlocking($fd, true);
        $data = $this->readLengthPrefixed($fd);
        return json_decode($data);
      case self::FORMAT_BYTE_STREAM:
        FileDescriptorManager::setBlocking($fd, false);
        $data = FileDescriptorManager::read($fd, 4096);
        if ($data === true) {
          // No data available yet (EAGAIN).
          return '';
        } else {
          return $data;
        }
      case self::FORMAT_NEWLINE_SEPARATED:
        FileDescriptorManager::setBlocking($fd, true);
        $buffer = '';
        $char = null;
        while ($char !== "\n") {
          $char = FileDescriptorManager::read($fd, 1);
          if ($char !== "\n") {
            $buffer .= $char;
          }
        }
        return $buffer;
      case self::FORMAT_NULL_SEPARATED:
        FileDescriptorManager::setBlocking($fd, true);
        $buffer = '';
        $char = null;
        while ($char !== "\0") {
          $char = FileDescriptorManager::read($fd, 1);
          if ($char !== "\0") {
            $buffer .= $char;
          }
        }
        return $buffer;
      default:
        throw new Exception('Unknown read format '.$this->readFormat);
    }
  }

  private function readLengthPrefixed($fd) {
    $length_bytes = FileDescriptorManager::read($fd, 4);
    
    $length_byte_1 = ord($length_bytes[0]);
    $length_byte_2 = ord($length_bytes[1]);
    $length_byte_3 = ord($length_bytes[2]);
    $length_byte_4 = ord($length_bytes[3]);
    $length = 
      ($length_byte_1 << 24) |
      ($length_byte_2 << 16) |
      ($length_byte_3 << 8) |
      $length_byte_4;
    return FileDescriptorManager::read($fd, $length);
  }
  
  public function close() {
    $this->closeRead();
    $this->closeWrite();
  }
  
  public function closeRead() {
    if ($this->nativePipe['read'] !== null) {
      if (!$this->closable) {
        omni_trace(
          "ignoring request to close read side of endpoint with read FD ".
          $this->getReadFD()."; endpoint is marked unclosable");
        return;
      }
      
      omni_trace("closing file descriptor ".$this->getReadFD()." because closeRead was called");
      FileDescriptorManager::close($this->getReadFD());
      $this->nativePipe['read'] = null;
    }
  }
  
  public function closeWrite() {
    if ($this->nativePipe['write'] !== null) {
      if (!$this->closable) {
        omni_trace(
          "ignoring request to close write side of endpoint with write FD ".
          $this->getWriteFD()."; endpoint is marked unclosable");
        return;
      }
      
      omni_trace("closing file descriptor ".$this->getWriteFD()." because closeWrite was called");
      FileDescriptorManager::close($this->getWriteFD());
      $this->nativePipe['write'] = null;
    }
  }
  
}