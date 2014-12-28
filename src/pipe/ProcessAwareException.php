<?php

final class ProcessAwareException extends Exception {
  
  protected $original;
  protected $processTrace = array();
  
  public function __construct(Exception $original) {
    $this->original = $original;
    $this->message = $original->getMessage();
    $this->code = $original->getCode();
    $this->file = $original->getFile();
    $this->line = $original->getLine();
  }
  
  public function addProcessTrace($message) {
    $this->processTrace[] = $message;
  }
  
  public function getProcessTrace() {
    $result = array();
    $idx = 0;
    foreach ($this->processTrace as $trace) {
      $result[] = "#$idx ".$trace;
      $idx++;
    }
    $result[] = "#$idx reported by ".posix_getpid();
    return $result;
  }
  
  public function getOriginal() {
    return $this->original;
  }
  
}