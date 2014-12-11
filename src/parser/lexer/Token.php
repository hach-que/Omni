<?php

abstract class Token extends Phobject {

  abstract function getRegex();
  
  public function getName() {
    return get_class($this);
  }

  public function canAccept($token_name) {
    return $this->getName() === $token_name;
  }
  
}