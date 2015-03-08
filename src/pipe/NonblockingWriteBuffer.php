<?php

/**
 * Provides an implementation of a non-blocking write buffer
 * for the pipe controller.
 * 
 * This is required because the following scenario may be present:
 *
 *  shell -> pipe -> shell
 * 
 * e.g. if a builtin is piping through to a lambda.
 *
 * In this scenario a deadlock may occur if both the shell and pipe
 * are writing to native pipes in blocking mode.  The native pipe
 * between the pipe and shell may fill because the lambda is not yet
 * reading data (because the shell is still processing the builtin).
 * When the native pipe between the pipe and the shell fills, the
 * pipe controller would then block on a write, preventing it from
 * reading from the native pipe between the shell and the pipe.  When
 * this occurs, that native pipe then fills, and the shell blocks on
 * writing out from the builtin.
 *
 * To work around this, we always performs writes from the pipe
 * controller through this class, which accepts data that would
 * normally be passed directly to fd_write and keeps track of where
 * and how much data has actually been written to the underlying file
 * descriptor (which is placed in a non-blocking mode by this class).
 */
final class NonblockingWriteBuffer extends Phobject {

  private $fd;
  private $buffer;

  public function __construct($fd) {
    $this->fd = $fd;
  }
  
  public function write($data) {
    $this->buffer .= $data;
  }
  
  public function flush() {
    $current = strlen($this->buffer);
    omni_trace("flushing non-blocking write buffer; $current bytes in buffer");
    try {
      omni_trace("change blocking mode to non-blocking");
      FileDescriptorManager::setBlocking($this->fd, false);
      omni_trace("write buffer contents");
      FileDescriptorManager::write($this->fd, $this->buffer);
      omni_trace("buffer entirely written!");
      $this->buffer = "";
    } catch (NativePipeNonblockingWriteNotReadyException $ex) {
      $this->buffer = $ex->getBufferRemaining();
      $current = strlen($this->buffer);
      omni_trace("buffer not entirely written; $current bytes now in buffer");
    }
    omni_trace("change blocking mode to blocking");
    FileDescriptorManager::setBlocking($this->fd, true);
  }
  
  public function isEmpty() {
    return strlen($this->buffer) === 0;
  }

}