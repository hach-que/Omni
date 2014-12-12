<?php

/**
 * An outbound object stream that retrieves characters from a file descriptor.
 *
 * This stream is often attached to a file descriptor for a native executable.
 */
final class OutboundFDStream extends OutboundStream {
  
  private $file;
  
  public function __construct($fd) {
    $this->file = fopen('php://fd/'.$fd, 'w');
    stream_set_blocking($this->file, false);
  }
  
  public function getSourceType() {
    // Each group is a collection of raw bytes of arbitrary length.  The pipe
    // mechanism understands that streams of TYPE_RAW_BYTES need to have
    // seperators applied before they can processed by other mechanisms.
    return TypeConverter::TYPE_RAW_BYTES;
  }
  
  public function canWrite() {
    $write_array = array($this->file);
    $changed_streams = stream_select(
      NULL,
      $write_array,
      NULL,
      0,
      0);
    return $changed_streams > 0;
  }
  
  public function write(array $objects) {
    assert_instances_of($objects, 'PhutilRope');
    foreach ($objects as $rope) {
      while ($rope->getByteLength() !== 0) {
        $written = phutil_fwrite_nonblocking_stream(
          $this->file,
          $rope->getAsString());
        
        if ($written === false) {
          // We can't write anything to the file descriptor.
          return;
        }
        
        $rope->removeBytesFromHead($written);
      }
    }
  }
  
}