<?php

final class Pipe extends Phobject {

  private $targetType;
  private $inboundStreams = array();
  private $outboundStreams = array();
  
  public function setTypeConversion($target_type) {
    $this->targetType = $target_type;
  }
  
  public function attachInbound(InboundStream $inbound) {
    $this->inboundStreams[] = $inbound;
  }
  
  public function attachOutbound(OutboundStream $outbound) {
    $this->outboundStreams[] = $outbound;
  }

  /**
   * Performs a single update step, taking objects from the
   * inbound streams and directing them to the outbound streams.
   */
  public function update() {
    
  }
  
  /** 
   * Joins the pipe; calls update() continously until all inbound
   * streams are exhausted.
   */
  public function join() {
    
  }
  
}