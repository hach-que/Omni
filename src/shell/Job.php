<?php

final class Job extends Phobject implements HasTerminalModesInterface {

  private $chainRoot = null;
  private $pgid = null;
  private $processes = array();
  private $foreground = true;
  private $terminalModes;
  private $userHasBeenNotifiedOfNewStatus = false;
  private $command;
  private $temporaryPipes;
  private $processesIgnoredForCompletion = array();
  
  public function hasUserBeenNotifiedOfNewStatus() {
    return $this->userHasBeenNotifiedOfNewStatus;
  }
  
  public function setUserBeenNotifiedOfNewStatus($notified) {
    $this->userHasBeenNotifiedOfNewStatus = $notified;
    return $this;
  }
  
  public function getCommand() {
    return $this->command;
  }
  
  public function setCommand($command) {
    $this->command = $command;
  }
  
  public function setForeground($foreground) {
    $this->foreground = $foreground;
    return $this;
  }
  
  public function isForeground() {
    return $this->foreground;
  }
  
  public function getExitCode() {
    $max = null;
    foreach ($this->processes as $process) {
      $value = $process->getExitCode();
      if ($value !== null && ($max === null || $value > $max)) {
        $max = $value;
      }
    }
    return $max;
  }
  
  public function setTerminalModes($terminal_modes) {
    $this->terminalModes = $terminal_modes;
    return $this;
  }
  
  public function getTerminalModes() {
    return $this->terminalModes;
  }
  
  public function getProcessGroupID($default_pid) {
    if ($this->pgid === null) {
      $this->pgid = $default_pid;
    }
    
    return $this->pgid;
  }
  
  public function getProcessGroupIDOrNull() {
    return $this->pgid;
  }
  
  public function addProcess(ProcessInterface $process) {
    $this->processes[] = $process;
  }
  
  public function getProcesses() {
    return $this->processes;
  }
  
  public function isStopped() {
    foreach ($this->processes as $process) {
      if (!$process->isStopped() && !$process->isCompleted()) {
        return false;
      }
    }
    
    return true;
  }
  
  public function isCompleted() {
    foreach ($this->processes as $process) {
      if (in_array($process, $this->processesIgnoredForCompletion)) {
        // Ignore for isCompleted status
        continue;
      }
      if (!$process->isCompleted()) {
        return false;
      }
    }
    
    return true;
  }
  
  public function finalize() {
  }
  
  public function getProcessGroupIDOrAssert() {
    if ($this->pgid === null) {
      throw new Exception(
        'The specified job does not have a process '.
        'group ID, so it can not be scheduled');
    }
    
    return $this->pgid;
  }
  
  public function setChainRoot($root) {
    $this->chainRoot = $root;
  }
  
  public function ignoreProcessForCompletion(ProcessInterface $process) {
    $this->processesIgnoredForCompletion[] = $process;
  }
  
  public function clearProcessesIgnoredForCompletion() {
    $this->processesIgnoredForCompletion = array();
  }
  
  /**
   * Returns true if this job has a single stage (with no
   * redirections or pipes), and the single stage will
   * execute a native command.
   *
   * In this situation, we can skip attachment of pipes
   * to the native process and just give it full control.
   * This ensures interactive commands like Vim, and
   * commands that output colours on interactive
   * terminals (like Arcanist) work correctly.
   */
  public function isPureNativeJob(Shell $shell) {
    $pipelines = $this->chainRoot->getPipelinesRecursively();
  
    if (count($pipelines) !== 1) {
      return false;
    }
  
    $stages = $pipelines[0]->getStages();
  
    if (count($stages) !== 1) {
      return false;
    }
    
    return $stages[0]->detectProcessType($shell) === 'native';
  }
  
  public function registerTemporaryPipe($pipe) {
    if ($this->temporaryPipes === null) {
      $this->temporaryPipes = array();
    }
    
    omni_trace("registered temporary pipe on job ".spl_object_hash($this));
    $this->temporaryPipes[] = $pipe;
  }
  
  public function getTemporaryPipes() {
    if ($this->temporaryPipes === null) {
      omni_trace("temporary pipes is null (no pipes are registered!) on job ".spl_object_hash($this));
      throw new Exception("temporary pipes is null (no pipes are registered!");
    }
    return $this->temporaryPipes;
  }
  
  public function killTemporaryPipes() {
    if ($this->temporaryPipes !== null) {
      foreach ($this->temporaryPipes as $pipe) {
        $pipe->killController();
      }
    }
  }
  
  public function closeTemporaryPipes() {
    if ($this->temporaryPipes !== null) {
      foreach ($this->temporaryPipes as $pipe) {
        $pipe->close();
      }
    }
  }
  
  public function untrackTemporaryPipes() {
    omni_trace("untrackTemporaryPipes called on job ".spl_object_hash($this));
    $this->temporaryPipes = null;
  }

  public function execute(
    Shell $shell,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr,
    $stdout_is_captured = false) {
    
    omni_trace("preparing chain root");
    
    $prepare_data = $this->chainRoot->prepare(
      $shell,
      $this,
      $stdin,
      $stdout,
      $stderr,
      false);
      
    omni_trace("executing chain root");
      
    $this->chainRoot->execute(
      $shell,
      $this,
      $prepare_data);
    
    omni_trace("scheduling job: ".$this->getCommand());
    
    $shell->scheduleJob($this);
    
    omni_trace("job execution complete: ".$this->getCommand());
  }
  
}