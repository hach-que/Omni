<?php

final class JsonContainer extends Phobject {
  
  private $data;
  
  public function __construct($encoded) {
    $this->data = phutil_json_decode($encoded);
  }
  
  public function __toString() {
    return json_encode($this->data);
  }
  
}