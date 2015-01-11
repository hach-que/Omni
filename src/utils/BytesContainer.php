<?php

final class BytesContainer extends Phobject {

  private $byteString;
  
  public function __construct($string) {
    $this->byteString = $string;
  }
  
  public function __toString() {
    return $this->byteString;
  }

}