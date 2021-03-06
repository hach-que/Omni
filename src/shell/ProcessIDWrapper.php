<?php

final class ProcessIDWrapper
  extends Phobject
  implements ProcessInterface {
  
  private $pid;
  private $status;
  private $stopped;
  private $completed;
  private $type;
  private $description;
  private $exitCode;
  
  public function __construct($pid, $type, $description) {
    $this->pid = $pid;
    $this->type = $type;
    $this->description = $description;
  }
  
  public function hasProcessID() {
    return $this->pid !== null;
  }
  
  public function getProcessID() {
    return $this->pid;
  }
  
  public function getProcessType() {
    return $this->type;
  }
  
  public function getProcessDescription() {
    return $this->description;
  }
  
  public function getProcessStatus() {
    return $this->status;
  }
  
  public function getExitCode() {
    return $this->exitCode;
  }
  
  public function setProcessStatus($status) {
    $this->status = $status;
  }
  
  public function isStopped() {
    return $this->stopped;
  }
  
  public function isCompleted() {
    return $this->completed;
  }
  
  public function setStopped($stopped) {
    return $this->stopped = $stopped;
  }
  
  public function setCompleted($completed) {
    return $this->completed = $completed;
  }
  
  public function setExitCode($exit_code) {
    $this->exitCode = $exit_code;
  }
  
}