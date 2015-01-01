<?php

final class MethodCallReference extends Phobject {

  private $target;
  private $name;
  
  public function __construct($target, $name) {
    $this->target = $target;
    $this->name = $name;
  }
  
  public function __toString() {
    return 'Method \''.$this->name.'\' on \''.get_class($this->target).'\' instance';
  }
  
  public function getCallback() {
    return array($this->target, $this->name);
  }
  
  public function call(array $argv) {
    return call_user_func_array($this->getCallback(), $argv);
  }

}