<?php

abstract class Symbol extends Phobject {

  abstract function getRules();

  public function canAccept($token_name, $seen = array()) {
    if (in_array(get_class($this), $seen)) {
      return false;
    }
  
    $seen[] = get_class($this);
    foreach ($this->getRules() as $rule) {
      if (is_string($rule)) {
        $sub = new $rule();
        if ($sub->canAccept($token_name, $seen)) {
          return true;
        }
      } else if (is_array($rule)) {
        foreach ($rule as $subn) {
          $sub = new $subn();
          if ($sub->canAccept($token_name, $seen)) {
            return true;
          }
        }
      }
    }
    
    return false;
  }
  
}
