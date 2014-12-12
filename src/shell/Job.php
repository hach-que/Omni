<?php

final class Job extends Phobject {

  private $stages = array();
  
  public function addStage($stage) {
    $this->stages[] = $stage;
  }
  
  public function getStages() {
    return $this->stages;
  }
  
  public function execute(Shell $shell, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    $pipe_prev = $stdin;
    $pipe_next = null;
  
    $pipes = array();
  
    $stage_count = count($this->stages);
    for ($i = 0; $i < $stage_count; $i++) {
      $stage = $this->stages[$i];
      
      if ($i !== $stage_count - 1) {
        // If this is not the last stage in the job, create
        // an Omni pipe for transferring objects.
        $pipe_next = new Pipe();
      } else {
        // If this is the last stage of the job, set the
        // next pipe as standard output.
        $pipe_next = $stdout;
      }
      
      $stage->launch($shell, $pipe_prev, $pipe_next, $stderr);
      
      $pipe_prev = $pipe_next;
    }
  }

}