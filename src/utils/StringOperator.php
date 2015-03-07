<?php

final class StringOperator extends Phobject {

  private $str;

  public function __construct($str) {
    $this->str = $str;
  }
  
  public function substr($start, $length = null) {
    if ($start < 0) {
      $start = strlen($this->str) + $start;
    }
    
    if ($length === null) {
      return substr($this->str, $start);
    } else {
      if ($length < 0) {
        $length = (strlen($this->str) + $length) - $start;
      }
      return substr($this->str, $start, $length);
    }
  }
  
  public function getLength() {
    return strlen($this->str);
  }

}