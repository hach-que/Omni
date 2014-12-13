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

  private $nativePipe;
  private $cachedReadStream;
  private $cachedWriteStream;
  private $writeFormat = self::FORMAT_PHP_SERIALIZATION;
  private $readFormat = self::FORMAT_PHP_SERIALIZATION;

  public function __construct() {
    $this->nativePipe = omni_pipe();
  }
  
  public function getReadFD() {
    return $this->nativePipe['read'];
  }
  
  public function getWriteFD() {
    return $this->nativePipe['write'];
  }
  
  public function getReadStream() {
    if ($this->cachedReadStream !== null) {
      return $this->cachedReadStream;
    }
    
    $this->cachedReadStream = fopen('php://fd/'.$this->getReadFD(), 'r');
    return $this->cachedReadStream;
  }
  
  public function getWriteStream() {
    if ($this->cachedWriteStream !== null) {
      return $this->cachedWriteStream;
    }
    
    $this->cachedWriteStream = fopen('php://fd/'.$this->getWriteFD(), 'w');
    return $this->cachedWriteStream;
  }
  
  public function setWriteFormat($format) {
    $this->writeFormat = $format;
  }
  
  public function setReadFormat($format) {
    $this->readFormat = $format;
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
        $data = (string)$object;
        break;
      case self::FORMAT_NEWLINE_SEPARATED:
        $data = ((string)$object)."\n";
        break;
      case self::FORMAT_NULL_SEPARATED:
        $data = ((string)$object)."\0";
        break;
    }
    $stream = $this->getWriteStream();
    fwrite($stream, $data);
    fflush($stream);
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
    $stream = $this->getReadStream();
    switch ($this->writeFormat) {
      case self::FORMAT_PHP_SERIALIZATION:
        stream_set_blocking($stream, true);
        $data = $this->readLengthPrefixed($stream);
        return unserialize($data);
      case self::FORMAT_LENGTH_PREFIXED_JSON:
        stream_set_blocking($stream, true);
        $data = $this->readLengthPrefixed($stream);
        return json_decode($data);
      case self::FORMAT_BYTE_STREAM:
        stream_set_blocking($stream, false);
        $data = fread($stream, 4096);
        if ($data !== false) {
          return $data;
        } else {
          throw new Exception('failed to read from pipe!');
        }
      case self::FORMAT_NEWLINE_SEPARATED:
        stream_set_blocking($stream, true);
        $buffer = '';
        $char = null;
        while ($char !== "\n") {
          $char = fread($stream, 1);
          if ($char !== "\n") {
            $buffer .= $char;
          }
        }
        return $buffer;
      case self::FORMAT_NULL_SEPARATED:
        stream_set_blocking($stream, true);
        $buffer = '';
        $char = null;
        while ($char !== "\0") {
          $char = fread($stream, 1);
          if ($char !== "\0") {
            $buffer .= $char;
          }
        }
        return $buffer;
      default:
        throw new Exception('Unknown read format '.$this->readFormat);
    }
  }

  private function readLengthPrefixed($stream) {
    $length_bytes = fread($stream, 4);
    $length_byte_1 = ord($length_bytes[0]);
    $length_byte_2 = ord($length_bytes[1]);
    $length_byte_3 = ord($length_bytes[2]);
    $length_byte_4 = ord($length_bytes[3]);
    $length = 
      ($length_byte_1 << 24) |
      ($length_byte_2 << 16) |
      ($length_byte_3 << 8) |
      $length_byte_4;
    return fread($stream, $length);
  }

}