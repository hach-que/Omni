<?php

final class ArrayContainer extends Phobject {

  private $array;

  public function __construct(array $array = null) {
    if ($array === null) {
      $array = array();
    }
    
    $this->array = $array;
  }
  
  public function getCopy() {
    return $this->array;
  }
  
  public function deepCopy() {
    $result = array();
    foreach ($this->array as $key => $value) {
      if ($value instanceof ArrayContainer) {
        $result[$key] = $value->deepCopy();
      } else {
        $result[$key] = $value;
      }
    }
    return $result;
  }
  
  public function &getReference() {
    return $this->array;
  }
  
  public function set($index, $value) {
    $this->array[$index] = $value;
    return $value;
  }
  
  public function get($index) {
    return $this->array[$index];
  }
  
  public function append($value) {
    $this->array[] = $value;
    return $value;
  }
  
  public function has($index) {
    return isset($this->array[$index]);
  }

}