<?php

final class Pipeline
  extends Phobject
  implements PipelineOrChainInterface {

  private $stages;
  private $command;
  
  public function setCommand($command) {
    $this->command = $command;
    return $this;
  }
  
  public function getCommand() {
    return $this->command;
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
  
  public function getPipelinesRecursively() {
    return array($this);
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr,
    $stdout_is_captured = false) {
    
    $pipe_prev = $stdin;
    $pipe_next = null;
    
    omni_trace("preparing pipeline execution: ".$this->getCommand());
  
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
          ->setShellAndJob($shell, $job);
        $job->registerTemporaryPipe($pipe_next);
      } else {
        omni_trace("stage $i is last job, pointing stdout at real stdout");
        
        // If this is the last stage of the job, set the
        // next pipe as standard output.
        $pipe_next = $stdout;
      }
      
      omni_trace("calling prepare() on ".get_class($stage));
        
      $stage_data[$i] = $stage->prepare($shell, $job, $pipe_prev, $pipe_next, $stderr);
      
      omni_trace("setting up for next stage in job");
      
      $pipe_prev = $pipe_next;
    }
    
    $close_read_on_fork = array();
    $close_write_on_fork = array();
    
    foreach ($stage_data as $stage) {
      $close_read_on_fork[] = idx($stage, 'close_read_on_fork', array());
      $close_write_on_fork[] = idx($stage, 'close_write_on_fork', array());
    }
    
    $close_read_on_fork = array_mergev($close_read_on_fork);
    $close_write_on_fork = array_mergev($close_write_on_fork);
    
    omni_trace("pipeline: there are ".count($close_read_on_fork)." endpoints to closeRead on after fork");
    omni_trace("pipeline: there are ".count($close_write_on_fork)." endpoints to closeWrite on after fork");
    
    return array(
      'stdin' => $stdin,
      'stdout' => $stdout,
      'stderr' => $stderr,
      'stdout_is_captured' => $stdout_is_captured,
      'stage_data' => $stage_data,
      'pipe_prev' => $pipe_prev,
      'pipe_next' => $pipe_next,
      'close_read_on_fork' => $close_read_on_fork,
      'close_write_on_fork' => $close_write_on_fork,
    );
  }  
    
  public function execute(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    $stdin = idx($prepare_data, 'stdin');
    $stdout = idx($prepare_data, 'stdout');
    $stderr = idx($prepare_data, 'stderr');
    $stdout_is_captured = idx($prepare_data, 'stdout_is_captured');
    $stage_data = idx($prepare_data, 'stage_data');
    $pipe_prev = idx($prepare_data, 'pipe_prev');
    $pipe_next = idx($prepare_data, 'pipe_next');
    
    omni_trace("starting pipeline execution: ".$this->getCommand());
    
    omni_trace("getting ready to launch pipes");
    
    if ($stdout_is_captured) {
      omni_trace("standard output is captured!");
    }
    
    omni_trace("i am PID ".posix_getpid());
    
    foreach ($job->getTemporaryPipes() as $pipe) {
      omni_trace("marking ".$pipe->getName()." as finalized");
      
      // This pipe won't be modified by us any more, so we should assume
      // all inbound and outbound endpoints that would have been connected, 
      // have been connected.  This needs to be done because if we're running
      // a builtin, it might not add an endpoint to standard input, which would
      // keep the standard input controller running (even when it should exit).
      if (!$pipe->isClosed()) {
        $pipe->markFinalized();
      }
      
      $process = $pipe->getControllerProcess(true);
      if ($process !== null) {
        $job->addProcess($process);
        
        if ($pipe === $stdout && $stdout_is_captured) {
          omni_trace("marking standard output process as ignored for completion");
          $job->ignoreProcessForCompletion($process);
        }
      }
    }
    
    omni_trace("getting ready to launch executables");
    
    $stage_count = count($this->stages);
    for ($i = 0; $i < $stage_count; $i++) {
      $stage = $this->stages[$i];
      $prepare_data = $stage_data[$i];
      
      omni_trace("calling launch() on ".get_class($stage));
        
      $result = $stage->launch($shell, $job, $prepare_data);
      
      omni_trace("adding result processes");
      
      if ($result === null) {
        continue;
      } else if ($result instanceof ProcessInterface) {
        $job->addProcess($result);
      } else if (is_array($result)) {
        foreach ($result as $a) {
          if ($a instanceof ProcessInterface) {
            $job->addProcess($a);
          }
        }
      } else {
        throw new Exception('Unknown return value from launching process');
      }
    }
    
    $has_external_processes = false;
    foreach ($job->getProcesses() as $process) {
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
    foreach ($job->getProcesses() as $process) {
      if (!$process->hasProcessID()) {
        continue;
      }
      
      omni_trace("putting ".$process->getProcessID()." into shell process group");
      
      $shell->putProcessInProcessGroupIfInteractive($job, $process->getProcessID());
    }
  }

}