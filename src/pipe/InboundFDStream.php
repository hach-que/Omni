<?php

/**
 * An inbound object stream that retrieves characters from a file descriptor.
 *
 * This stream is often attached to a file descriptor for a native executable.
 */
final class InboundFDStream extends InboundStream {
  
  private $file;
  
  public function __construct($fd) {
    $this->file = fopen('php://fd/'.$fd, 'r');
    stream_set_blocking($this->file, false);
  }
  
  public function getSourceType() {
    // Each group is a collection of raw bytes of arbitrary length.  The pipe
    // mechanism understands that streams of TYPE_RAW_BYTES need to have
    // seperators applied before they can processed by other mechanisms.
    return TypeConverter::TYPE_RAW_BYTES;
  }
  
  public function canRead() {
    $read_array = array($this->file);
    $changed_streams = stream_select(
      $read_array,
      NULL,
      NULL,
      0,
      0);
    return $changed_streams > 0;
  }
  
  public function read() {
    $rope = new PhutilRope();
    
    do {
      $result = fread($this->file, 4096);
      $rope->append($result);
    } while (strlen($result) > 0);
    
    return $rope;
  }
  
  public function isExhausted() {
    return feof($this->file);
  }
  
}