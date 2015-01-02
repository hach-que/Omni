<?php

final class ReturnFlowControlException extends Exception {

  private $returnValue;

  public function __construct($return_value) {
    $this->returnValue = $return_value;
  }
  
  public function getReturnValue() {
    return $this->returnValue;
  }

}