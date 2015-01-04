<?php

final class SocketEndpoint extends BaseEndpoint {

  private $socket;

  public function __construct($socket) {
    $this->socket = $socket;
  }
  
  public function getWriteFormat() {
    return $this->writeFormat;
  }
  
  public function getReadFormat() {
    return $this->readFormat;
  }
  
  public function setWriteFormat($format) {
    $this->writeFormat = $format;
    return $this;
  }
  
  public function setReadFormat($format) {
    $this->readFormat = $format;
    return $this;
  }
  
  
  /* ----- ( BaseEndpoint ) ------------------------------------------------- */
  
  
  protected function writeInternal($data) {
    return socket_write($this->socket, $data, strlen($data));
  }
  
  protected function setReadBlockingInternal($blocking) {
    if ($blocking) {
      socket_set_block($this->socket);
    } else {
      socket_set_nonblock($this->socket);
    }
  }
    
  protected function readInternal($length) {
    return socket_read($this->socket, $length);
  }
  
  public function close() {
    socket_close($this->socket);
  }
  
  
}