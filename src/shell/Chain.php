<?php

final class Chain
  extends Phobject
  implements PipelineOrChainInterface {

  private $left;
  private $op;
  private $right;
  
  public function __construct($left, $op, $right) {
    $this->left = $left;
    $this->op = $op;
    $this->right = $right;
  }

  public function getLeft() {
    return $this->left;
  }
  
  public function getOp() {
    return $this->op;
  }
  
  public function getRight() {
    return $this->right;
  }
  
  public function getPipelinesRecursively() {
    $pipelines = array();
    
    foreach ($this->left->getPipelinesRecursively() as $pipeline) {
      $pipelines[] = $pipeline;
    }
    
    foreach ($this->right->getPipelinesRecursively() as $pipeline) {
      $pipelines[] = $pipeline;
    }
    
    return $pipelines;
  }
  
  public function getCommand() {
    $op = ' ?? ';
    if ($this->op === 'or') { $op = ' || '; }
    if ($this->op === 'and') { $op = ' && '; }
    return $this->left->getCommand().$op.$this->right->getCommand();
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr,
    $stdout_is_captured = false) {
        
    omni_trace("preparing chain node for '".$this->getCommand()."'");
    
    omni_trace("left chain node is '".$this->left->getCommand()."'");
    omni_trace("right chain node is '".$this->right->getCommand()."'");
    
    $left_job = new Job();
    $left_job->setCommand($this->left->getCommand());
    $left_data = $this->left->prepare(
      $shell,
      $left_job,
      $stdin,
      $stdout,
      $stderr,
      $stdout_is_captured);
    
    $right_job = new Job();
    $right_job->setCommand($this->right->getCommand());
    $right_data = $this->right->prepare(
      $shell,
      $right_job,
      $stdin,
      $stdout,
      $stderr,
      $stdout_is_captured);
      
    omni_trace("after prepare, left job hash is ".spl_object_hash($left_job));
    omni_trace("after prepare, right job hash is ".spl_object_hash($right_job));
      
    $close_read_on_fork = array(
      idx($left_data, 'close_read_on_fork', array()),
      idx($right_data, 'close_read_on_fork', array()),
    );
    $close_write_on_fork = array(
      idx($left_data, 'close_write_on_fork', array()),
      idx($right_data, 'close_write_on_fork', array()),
    );
    
    $close_read_on_fork = array_mergev($close_read_on_fork);
    $close_write_on_fork = array_mergev($close_write_on_fork);
    
    omni_trace("chain: there are ".count($close_read_on_fork)." endpoints to closeRead on after fork");
    omni_trace("chain: there are ".count($close_write_on_fork)." endpoints to closeWrite on after fork");
      
    return array(
      'stdin' => $stdin,
      'stdout' => $stdout,
      'stderr' => $stderr,
      'stdout_is_captured' => $stdout_is_captured,
      'left_job' => $left_job,
      'left_data' => $left_data,
      'right_job' => $right_job,
      'right_data' => $right_data,
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
    $left_job = idx($prepare_data, 'left_job');
    $left_data = idx($prepare_data, 'left_data');
    $right_job = idx($prepare_data, 'right_job');
    $right_data = idx($prepare_data, 'right_data');
    $close_read_on_fork = idx($prepare_data, 'close_read_on_fork');
    $close_write_on_fork = idx($prepare_data, 'close_write_on_fork');
    
    omni_trace("encountered chain node for '".$this->getCommand()."'; fork is required to proceed");
    
    omni_trace("during execute, left job hash is ".spl_object_hash($left_job));
    omni_trace("during execute, right job hash is ".spl_object_hash($right_job));
    
    $pid = pcntl_fork();
    if ($pid === 0) {
      omni_trace("i am the chain controller");
      
      omni_trace("reinitializing shell in non-interactive mode");
      $shell->initialize(true);
      
      try {
        switch ($this->op) {
          case 'or':
          case 'and':
            if ($this->op === 'or') {
              omni_trace("performing 'or' chain logic");
            } else {
              omni_trace("performing 'and' chain logic");
            }
            
            omni_trace("executing left hand side");
            
            try {
              $this->left->execute(
                $shell,
                $left_job,
                $left_data,
                $stdout_is_captured);
            } catch (Exception $ex) {
              omni_trace("encountered exception during left hand side execution");
              omni_trace("killing pipes for left and right jobs");
              $left_job->killTemporaryPipes();
              $right_job->killTemporaryPipes();
              omni_trace("rethrowing exception");
              throw $ex;
            }
              
            $shell->scheduleJob($left_job);
            
            omni_trace("closing and untracking pipes for left job");
            
            $left_job->closeTemporaryPipes();
            $left_job->untrackTemporaryPipes();
            
            omni_trace("left job exit code is ".$left_job->getExitCode());
            
            $execute_right = false;
            if ($this->op === 'or' && $left_job->getExitCode() !== 0) {
              $execute_right = true;
            }
            if ($this->op === 'and' && $left_job->getExitCode() === 0) {
              $execute_right = true;
            }
            
            if ($execute_right) {
              omni_trace("executing right hand side");
              
              try {
                $this->right->execute(
                  $shell,
                  $right_job,
                  $right_data,
                  $stdout_is_captured);
              } catch (Exception $ex) {
                omni_trace("encountered exception during right hand side execution");
                omni_trace("killing pipes for right job");
                $right_job->killTemporaryPipes();
                omni_trace("rethrowing exception");
                throw $ex;
              }
                
              $shell->scheduleJob($right_job);
              
              omni_trace("closing and untracking pipes for right job");
              $right_job->closeTemporaryPipes();
              $right_job->untrackTemporaryPipes();
            
              omni_trace("exiting with exit code from right job ".$right_job->getExitCode());
              omni_exit($right_job->getExitCode());
            } else {
              omni_trace("killing pipes for right job");
              $right_job->killTemporaryPipes();
              
              omni_trace("exiting with exit code from left job ".$left_job->getExitCode());
              omni_exit($left_job->getExitCode());
            }
            
            break;
        }
        
        omni_trace("unknown operation for chain process");
        omni_exit(1);
      } catch (Exception $ex) {
        omni_trace((string)$ex);
        omni_exit(1);
      }
    } else if ($pid > 0) {
      omni_trace("i am the chain parent process, with child pid $pid");
      
      foreach ($close_read_on_fork as $endpoint) {
        $endpoint->closeRead();
        omni_trace("closed read endpoint ".$endpoint->getName()." due to fork");
      }
      foreach ($close_write_on_fork as $endpoint) {
        $endpoint->closeWrite();
        omni_trace("closed write endpoint ".$endpoint->getName()." due to fork");
      }
      
      $child = new ProcessIDWrapper($pid, 'chain', $this->getCommand());
      $job->addProcess($child);
      $shell->putProcessInProcessGroupIfInteractive($job, $pid);
    } else {
      throw new Exception('Unable to fork for chain controller.');
    }
  }
  
}