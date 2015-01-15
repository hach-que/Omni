<?php

final class Job extends Phobject implements HasTerminalModesInterface {

  private $stages = array();
  private $pgid = null;
  private $processes = array();
  private $foreground = true;
  private $terminalModes;
  private $userHasBeenNotifiedOfNewStatus = false;
  private $command;
  private $temporaryPipes;
  
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
  
  public function addStage($stage) {
    if ($stage instanceof OmniFunction) {
      $this->stages[] = new Process(array($stage), $stage->getOriginal());
    } else {
      $this->stages[] = $stage;
    }
  }
  
  public function getStages() {
    return $this->stages;
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
    $this->temporaryPipes = null;
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
    if (count($this->stages) !== 1) {
      return false;
    }
    
    return $this->stages[0]->detectProcessType($shell) === 'native';
  }
  
  public function execute(
    Shell $shell,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    $pipe_prev = $stdin;
    $pipe_next = null;
    
    omni_trace("starting job execution: ".$this->getCommand());
  
    $this->temporaryPipes = array();
    $this->temporaryPipes[] = $stdin;
    $this->temporaryPipes[] = $stdout;
    $this->temporaryPipes[] = $stderr;
    
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
        $pipe_next = id(new Pipe())
          ->setShellAndJob($shell, $this);
        $this->temporaryPipes[] = $pipe_next;
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
    
    foreach ($this->temporaryPipes as $pipe) {
      omni_trace("marking ".$pipe->getName()." as finalized");
      
      // This pipe won't be modified by us any more, so we should assume
      // all inbound and outbound endpoints that would have been connected, 
      // have been connected.  This needs to be done because if we're running
      // a builtin, it might not add an endpoint to standard input, which would
      // keep the standard input controller running (even when it should exit).
      $pipe->markFinalized();
      
      $process = $pipe->getControllerProcess(true);
      if ($process !== null) {
        $this->processes[] = $process;
      }
    }
    
    omni_trace("getting ready to launch executables");
    
    $stage_count = count($this->stages);
    for ($i = 0; $i < $stage_count; $i++) {
      $stage = $this->stages[$i];
      $prepare_data = $stage_data[$i];
      
      omni_trace("calling launch() on ".get_class($stage));
        
      $result = $stage->launch($shell, $this, $prepare_data);
      
      omni_trace("adding result processes");
      
      if ($result === null) {
        continue;
      } else if ($result instanceof ProcessInterface) {
        $this->processes[] = $result;
      } else if (is_array($result)) {
        foreach ($result as $a) {
          if ($a instanceof ProcessInterface) {
            $this->processes[] = $a;
          }
        }
      } else {
        throw new Exception('Unknown return value from launching process');
      }
    }
    
    $has_external_processes = false;
    foreach ($this->processes as $process) {
      if ($process->hasProcessID()) {
        $has_external_processes = true;
      }
    }
    
    if (!$has_external_processes) {
      omni_trace(
        "launch of job did not create any external processes;".
        "skipping process grouping and scheduling");
      return;
    }
    
    omni_trace("about to put processes into shell process group");
    
    // Put all of the native processes of this job into the
    // job's process group.
    foreach ($this->processes as $process) {
      if (!$process->hasProcessID()) {
        continue;
      }
      
      omni_trace("putting ".$process->getProcessID()." into shell process group");
      
      $shell->putProcessInProcessGroupIfInteractive($this, $process->getProcessID());
    }
    
    omni_trace("scheduling job: ".$this->getCommand());
    
    $shell->scheduleJob($this);
    
    omni_trace("job execution complete: ".$this->getCommand());
  }

}