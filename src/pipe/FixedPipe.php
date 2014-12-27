<?php

/**
 * An implementation of Pipe that replaces all of the logic with fake methods,
 * instead providing a fixed native pipe underneath.  This is used for
 * standard input (and standard output for single jobs).
 */
final class FixedPipe extends Pipe {

  private $fd;
  private $isInbound;

  public function __construct($fd, $is_inbound) {
    $this->fd = $fd;
    $this->isInbound = $is_inbound;
  }

  public function setTypeConversion($target_type) {
    throw new Exception('setTypeConversion not supported on FixedPipe');
  }
  
  public function setDistributionMethod($dist_method) {
    throw new Exception('setDistributionMethod not supported on FixedPipe');
  }
  
  public function setDefaultInboundFormat($format) {
    throw new Exception('setDefaultInboundFormat not supported on FixedPipe');
  }
  
  public function setDefaultOutboundFormat($format) {
    throw new Exception('setDefaultOutboundFormat not supported on FixedPipe');
  }
  
  public function getControllerProcess() {
    return null;
  }
  
  public function createInboundEndpoint($format = null, $name = null) {
    if ($this->isInbound) {
      throw new Exception('createInboundEndpoint not supported on FixedPipe');
    }
    
    return id(new Endpoint(array('read' => null, 'write' => $this->fd)))
      ->setClosable(false);
  }
  
  public function createOutboundEndpoint($format = null, $name = null) {
    if (!$this->isInbound) {
      throw new Exception('createOutboundEndpoint not supported on FixedPipe');
    }
    
    return id(new Endpoint(array('read' => $this->fd, 'write' => null)))
      ->setClosable(false);
  }
  
  public function attachStdinEndpoint($format = null, $name = null) {
    throw new Exception('attachStdinEndpoint not supported on FixedPipe');
  }
  
  public function attachStdoutEndpoint($format = null, $name = null) {
    throw new Exception('attachStdoutEndpoint not supported on FixedPipe');
  }
  
  public function attachStderrEndpoint($format = null, $name = null) {
    throw new Exception('attachStderrEndpoint not supported on FixedPipe');
  }
  
  public function getName() {
    return 'fixed pipe';
  }

  public function hasEndpoints() {
    return true;
  }
  
  public function dispatchControlEndpointCallEvent($index, $type, $function_name, $argv) {
    // This has no effect.
  }

}