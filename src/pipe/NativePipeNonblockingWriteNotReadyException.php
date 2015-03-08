<?php

final class NativePipeNonblockingWriteNotReadyException extends Exception {

  private $bufferRemaining;

  public function __construct($buffer_remaining) {
    $this->bufferRemaining = $buffer_remaining;
  }
  
  public function getBufferRemaining() {
    return $this->bufferRemaining;
  }

}