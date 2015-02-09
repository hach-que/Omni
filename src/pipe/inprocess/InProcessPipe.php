<?php

final class InProcessPipe extends Phobject implements PipeInterface {
  
  private $objects = array();
  
  public function getName() {
    return 'in-process pipe';
  }
  
  public function getControllerProcess() {
    return null;
  }
  
  public function createInboundEndpoint($format = null, $name = null) {
    return new InProcessEndpoint($this);
  }
  
  public function createOutboundEndpoint($format = null, $name = null) {
    return new InProcessEndpoint($this);
  }
  
  public function read() {
    if (count($this->objects) === 0) {
      throw new NativePipeClosedException();
    }
    
    return array_pop($this->objects);
  }
  
  public function write($object) {
    $this->objects[] = $object;
  }

  public function killController() {
  }
  
  public function markFinalized() {
  }
  
  public function close() {
  }

}