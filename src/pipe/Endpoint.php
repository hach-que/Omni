<?php

/**
 * The class which actually wraps a native system pipe, serializing and
 * deserializing data as needed.
 */
final class Endpoint extends BaseEndpoint {

  private $nativePipe;
  private $nativePipePending;
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
  
  public function getWriteFormat() {
    return $this->writeFormat;
  }
  
  public function getReadFormat() {
    return $this->readFormat;
  }
  
  public function instantiatePipe() {
    if ($this->nativePipePending) {
      $this->nativePipe = FileDescriptorManager::createPipe('endpoint read', 'endpoint write');
      $this->nativePipePending = false;
      omni_trace("created a pipe with read FD ".$this->getReadFD()." and write FD ".$this->getWriteFD());
    }
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
  
  
  /* ----- ( BaseEndpoint ) ------------------------------------------------- */
  
  
  protected function writeInternal($data) {
    return FileDescriptorManager::write($this->getWriteFD(), $data);
  }
  
  protected function setReadBlockingInternal($blocking) {
    FileDescriptorManager::setBlocking($this->getReadFD(), $blocking);
  }
    
  protected function readInternal($length) {
    return FileDescriptorManager::read($this->getReadFD(), $length);
  }
  
}