<?php

interface PipeInterface {

  public function getName();
  
  public function getControllerProcess();
  
  public function createInboundEndpoint($format = null, $name = null);
  
  public function createOutboundEndpoint($format = null, $name = null);

  public function killController();
  
  public function markFinalized();
  
}