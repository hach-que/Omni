<?php

final class InProcessEndpoint extends Phobject {

  private $pipe;

  public function __construct(InProcessPipe $pipe) {
    $this->pipe = $pipe;
  }
  
  public function read() {
    return $this->pipe->read();
  }
  
  public function write($object) {
    $this->pipe->write($object);
  }
  
  public function closeRead() { }
  
  public function closeWrite() { }
  
}