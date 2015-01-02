<?php

final class VariableManager extends Phobject {

  private $shell;
  private $scopes = array();
  
  public function __construct(Shell $shell) {
    $this->shell = $shell;
    $this->scopes[] = array();
  }
  
  public function beginVariableScope() {
    $this->scopes[] = array();
  }
  
  public function endVariableScope() {
    array_pop($this->scopes);
  }
  
  public function setVariable($key, $value) {
    if ($key === 'true' || $key === 'false' || $key === 'null') {
      throw new Exception('You can not set the value of constant $'.$key);
    }
    
    if ($key === '?') {
      throw new Exception('The $? variable is read-only');
    }
    
    $this->scopes[count($this->scopes) - 1][$key] = $value;
  }
  
  public function getVariable($key) {
    if ($key === 'true') {
      return true;
    }
    
    if ($key === 'false') {
      return false;
    }
    
    if ($key === 'null') {
      return null;
    }
    
    if ($key === '?') {
      return $this->shell->getLastExitCode();
    }
     
    for ($i = count($this->scopes) - 1; $i >= 0; $i--) {
      if (isset($this->scopes[$i][$key])) {
        return $this->scopes[$i][$key];
      }
    }
    
    throw new Exception('The variable $'.$key.' is not set.');
  }

}