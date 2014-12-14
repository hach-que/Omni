<?php

final class Job extends Phobject implements HasTerminalModesInterface {

  private $stages = array();
  private $pgid = null;
  private $processes = array();
  private $foreground = true;
  private $terminalModes;
  private $userHasBeenNotifiedOfNewStatus = false;
  private $killPipesOnJobCompletion = array();
  
  public function hasUserBeenNotifiedOfNewStatus() {
    return $this->userHasBeenNotifiedOfNewStatus;
  }
  
  public function setUserBeenNotifiedOfNewStatus($notified) {
    $this->userHasBeenNotifiedOfNewStatus = $notified;
    return $this;
  }
  
  public function getCommand() {
    return 'command information not available';
  }
  
  public function setForeground($foreground) {
    $this->foreground = $foreground;
    return $this;
  }
  
  public function isForeground() {
    return $this->foreground;
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
  
  public function getProcesses() {
    return $this->processes;
  }
  
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
  
  public function finalize() {
    $this->killRemainingPipes();
  }
  
  private function killRemainingPipes() {
    foreach ($this->killPipesOnJobCompletion as $pipe) {
      $pipe->killController();
    }
    
    $this->killPipesOnJobCompletion = array();
  }
  
  public function getProcessGroupIDOrAssert() {
    if ($this->pgid === null) {
      throw new Exception(
        'The specified job does not have a process '.
        'group ID, so it can not be scheduled');
    }
    
    return $this->pgid;
  }
  
  public function addStage($stage) {
    $this->stages[] = $stage;
  }
  
  public function getStages() {
    return $this->stages;
  }
  
  public function execute(Shell $shell, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    $pipe_prev = $stdin;
    $pipe_next = null;
    
    omni_trace("starting job execution");
  
    $pipes = array();
    $pipes[] = $stdout;
    $pipes[] = $stderr;
    
    // We actually want to kill these pipe controllers on job completion, because
    // some programs will ignore standard input (and therefore if the program connected
    // to stdin exits, we know we can terminate the input controller as well).
    $this->killPipesOnJobCompletion[] = $stdin;
    
    omni_trace("keep track of pipes to run");
    
    omni_trace("start launching stages");
  
    $stage_data = array();
    $stage_count = count($this->stages);
    for ($i = 0; $i < $stage_count; $i++) {
      $stage = $this->stages[$i];
    
      omni_trace("visiting stage $i: ".get_class($stage));
      
      if ($i !== $stage_count - 1) {
        omni_trace("stage $i is not last job, creating new pipe for stdout");
        
        // If this is not the last stage in the job, create
        // an Omni pipe for transferring objects.
        $pipe_next = new Pipe();
        $pipes[] = $pipe_next;
      } else {
        omni_trace("stage $i is last job, pointing stdout at real stdout");
        
        // If this is the last stage of the job, set the
        // next pipe as standard output.
        $pipe_next = $stdout;
      }
      
      omni_trace("calling prepare() on ".get_class($stage));
        
      $stage_data[$i] = $stage->prepare($shell, $this, $pipe_prev, $pipe_next, $stderr);
      
      omni_trace("setting up for next stage in job");
      
      $pipe_prev = $pipe_next;
    }
    
    omni_trace("getting ready to launch pipes");
    
    omni_trace("i am PID ".posix_getpid());
    
    omni_trace("starting pipe ".$stdin->getName());
    $stdin->startController($shell, $this);
    
    foreach ($pipes as $pipe) {
      omni_trace("starting pipe ".$pipe->getName());
    
      $this->processes[] = $pipe->startController($shell, $this);
    }
    
    omni_trace("getting ready to launch executables");
    
    $stage_count = count($this->stages);
    for ($i = 0; $i < $stage_count; $i++) {
      $stage = $this->stages[$i];
      $prepare_data = $stage_data[$i];
      
      omni_trace("calling launch() on ".get_class($stage));
        
      $result = $stage->launch($shell, $this, $prepare_data);
      
      omni_trace("adding result processes");
      
      if ($result instanceof ProcessInterface) {
        $this->processes[] = $result;
      } elseif (is_array($result)) {
        foreach ($result as $a) {
          if ($a instanceof ProcessInterface) {
            $this->processes[] = $a;
          }
        }
      }
    }
    
    omni_trace("about to put processes into shell process group");
    
    // Put all of the native processes of this job into the
    // job's process group.
    foreach ($this->processes as $process) {
      omni_trace("putting ".$process->getProcessID()." into shell process group");
      
      $shell->putProcessInProcessGroupIfInteractive($this, $process->getProcessID());
    }
    
    omni_trace("scheduling job");
    
    $shell->scheduleJob($this);
    
    omni_trace("job execution complete");
  }

}