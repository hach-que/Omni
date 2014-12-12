<?php

final class JobLegacy extends Phobject {
  
  public $command;
  public $processes;
  public $pgid;
  public $notified;
  public $tmodes;
  public $stdin;
  public $stdout;
  public $stderr;
  
  public function isStopped() {
    foreach ($this->processes as $process) {
      if (!$process->isStopped() || !$process->isCompleted()) {
	return false;
      }
    }
    
    return true;
  }
  
  public function isCompleted() {
    foreach ($this->processes as $process) {
      if (!$process->isCompleted()) {
	return false;
      }
    }
    
    return true;
  }
  
}