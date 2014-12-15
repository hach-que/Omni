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

  public function __construct($pipe = null) {
    if ($pipe === null) {
      $this->nativePipe = null; /* Pipe is pending until startController */
      $this->nativePipePending = true;
    } else {
      $this->nativePipe = $pipe;
      $this->nativePipePending = false;
    }
  }
  
  public function setName($name) {
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
    $this->writeFormat = $format;
    return $this;
  }
  
  public function setReadFormat($format) {
    $this->readFormat = $format;
    return $this;
  }
  
  public function instantiatePipe() {
    if ($this->nativePipePending) {
      $this->nativePipe = fd_pipe();
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
    
    $buffer = $data;
    $to_write = strlen($data);
    $written = 0;
    do {
      $result = fd_write($this->getWriteFD(), $buffer);
      if ($result === false) {
        // Error.
        throw new Exception('Write error');
      } else if ($result === true) {
        // Non-blocking; not ready for write.
        usleep(5000);
      } else if ($result === null) {
        // Pipe closed.
        throw new NativePipeClosedException();
      } else {
        // Wrote $result bytes.
        $written += $result;
        if ($written < $to_write) {
          $buffer = substr($data, $written);
        }
      }
    } while ($written < $to_write);
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
    
    switch ($this->writeFormat) {
      case self::FORMAT_PHP_SERIALIZATION:
        fd_set_blocking($fd, true);
        $data = $this->readLengthPrefixed($fd);
        return unserialize($data);
      case self::FORMAT_LENGTH_PREFIXED_JSON:
        fd_set_blocking($fd, true);
        $data = $this->readLengthPrefixed($fd);
        return json_decode($data);
      case self::FORMAT_BYTE_STREAM:
        fd_set_blocking($fd, false);
        $data = fd_read($fd, 4096);
        if ($data === true) {
          // No data available yet (EAGAIN).
          return '';
        } else if ($data === false) {
          // Other error.
          throw new Exception('Read error');
        } else if ($data === null) {
          // Pipe closed.
          throw new NativePipeClosedException();
        } else {
          return $data;
        }
      case self::FORMAT_NEWLINE_SEPARATED:
        fd_set_blocking($fd, true);
        $buffer = '';
        $char = null;
        while ($char !== "\n") {
          $char = fd_read($fd, 1);
          if ($char === null) {
            // Pipe closed.
            throw new NativePipeClosedException();
          }
          if ($char !== "\n") {
            $buffer .= $char;
          }
        }
        return $buffer;
      case self::FORMAT_NULL_SEPARATED:
        fd_set_blocking($fd, true);
        $buffer = '';
        $char = null;
        while ($char !== "\0") {
          $char = fd_read($fd, 1);
          if ($char === null) {
            // Pipe closed.
            throw new NativePipeClosedException();
          }
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
    $length_bytes = fd_read($fd, 4);
    if ($length_bytes === null) {
      throw new NativePipeClosedException();
    }
    
    $length_byte_1 = ord($length_bytes[0]);
    $length_byte_2 = ord($length_bytes[1]);
    $length_byte_3 = ord($length_bytes[2]);
    $length_byte_4 = ord($length_bytes[3]);
    $length = 
      ($length_byte_1 << 24) |
      ($length_byte_2 << 16) |
      ($length_byte_3 << 8) |
      $length_byte_4;
    $data = fd_read($fd, $length);
    if ($data === null) {
      throw new NativePipeClosedException();
    }
    return $data;
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
      
      fd_close($this->getReadFD());
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
      
      fd_close($this->getWriteFD());
      $this->nativePipe['write'] = null;
    }
  }
  
}