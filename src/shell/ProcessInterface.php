<?php

interface ProcessInterface {

  public function hasProcessID();

  public function getProcessID();

  public function getProcessType();

  public function getProcessDescription();
  
  public function getProcessStatus();
  
  public function getExitCode();
  
  public function setProcessStatus($status);

  public function isStopped();
  
  public function isCompleted();
  
  public function setStopped($stopped);
  
  public function setCompleted($completed);
  
  public function setExitCode($exit_code);
  
}