<?php

abstract class BaseEndpoint extends Phobject {

  const FORMAT_PHP_SERIALIZATION = 'php-serialization';
  const FORMAT_LENGTH_PREFIXED_JSON = 'json-lp';
  const FORMAT_BYTE_STREAM = 'byte-stream';
  const FORMAT_NEWLINE_SEPARATED = 'newline-separated';
  const FORMAT_NULL_SEPARATED = 'null-separated';
  const FORMAT_USER_FRIENDLY = 'user-friendly';
  
  protected $writeFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
  protected $readFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
  private $userFriendlyFormatter = null;
  
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

  protected function convertToStringIfNeeded($object) {
    if ($object instanceof BytesContainer) {
      return (string)$object;
    }
    
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
        if ($object instanceof BytesContainer) {
          $object = (string)$object;
        }
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
        $this->userFriendlyFormatter->clearSeenCache();
        $data = $this->userFriendlyFormatter->get($object);
        break;
    }
    
    $this->writeInternal($data);
  }
  
  protected abstract function writeInternal($data);
  
  public abstract function close();
  
  protected abstract function setReadBlockingInternal($blocking);
  
  protected abstract function readInternal($length);
  
  public function read() {
    switch ($this->writeFormat) {
      case self::FORMAT_PHP_SERIALIZATION:
        $this->setReadBlockingInternal(true);
        $data = $this->readLengthPrefixed();
        return unserialize($data);
      case self::FORMAT_LENGTH_PREFIXED_JSON:
        $this->setReadBlockingInternal(true);
        $data = $this->readLengthPrefixed();
        return json_decode($data);
      case self::FORMAT_BYTE_STREAM:
        $this->setReadBlockingInternal(false);
        $data = $this->readInternal(4096, false);
        if ($data === true) {
          // No data available yet (EAGAIN).
          return '';
        } else {
          return new BytesContainer($data);
        }
      case self::FORMAT_NEWLINE_SEPARATED:
        $this->setReadBlockingInternal(true);
        $buffer = '';
        $char = null;
        while ($char !== "\n") {
          $char = $this->readInternal(1);
          if ($char !== "\n") {
            $buffer .= $char;
          }
        }
        omni_trace("RETURNING BUFFER");
        return $buffer;
      case self::FORMAT_NULL_SEPARATED:
        $this->setReadBlockingInternal(true);
        $buffer = '';
        $char = null;
        while ($char !== "\0") {
          $char = $this->readInternal(1);
          if ($char !== "\0") {
            $buffer .= $char;
          }
        }
        return $buffer;
      default:
        throw new Exception('Unknown read format '.$this->readFormat);
    }
  }

  private function readLengthPrefixed() {
    $length_bytes = $this->readInternal(4);
    
    $length_byte_1 = ord($length_bytes[0]);
    $length_byte_2 = ord($length_bytes[1]);
    $length_byte_3 = ord($length_bytes[2]);
    $length_byte_4 = ord($length_bytes[3]);
    $length = 
      ($length_byte_1 << 24) |
      ($length_byte_2 << 16) |
      ($length_byte_3 << 8) |
      $length_byte_4;
    return $this->readInternal($length);
  }
  
}