<?php

final class PipelineVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    omni_trace("constructing job");
    
    $job = new Job();
    $job->setCommand($data['original']);
    foreach ($data['children'] as $child) {
      $job->addStage($this->visitChild($shell, $child));
    }
    
    $requires_inprocess_pipes = false;
    if ($data['data'] === 'expression') {
      omni_trace("check for in-process pipes");
      
      foreach ($job->getStages() as $stage) {
        if ($stage instanceof Process && $stage->useInProcessPipes($shell)) {
          if (count($job->getStages()) > 1) {
            throw new Exception($stage->getProcessDescription().' can not be piped');
          }
          
          $requires_inprocess_pipes = true;
        }
      }
    }
    
    omni_trace("setting up pipes for stdin / stdout / stderr");
    
    $stdin_pipe = new Pipe();
    $stdout_pipe = $requires_inprocess_pipes ? id(new InProcessPipe()) : id(new Pipe());
    $stderr_pipe = new Pipe();
    
    try {
      omni_trace("configuring job background / foreground expression before execution");
      
      if ($data['data'] !== 'expression') {
        $stdout_pipe->attachStdoutEndpoint(PipeDefaults::$stdoutFormat);
      }
      
      $stderr_pipe->attachStderrEndpoint(PipeDefaults::$stderrFormat);
      
      if ($data['data'] === 'foreground') {
        $job->setForeground(true);
        
        // FIXME As soon as attachStdinEndpoint is run, the standard input controller
        // starts.  If there's an exception later on, we aren't killing the standard
        // input controller (or any of the other controllers for that matter).  We
        // probably need to give jobs an exception property, and then write any
        // exception that occurs to that property, before finally sending SIGKILL to
        // any processes in the job (in addition, we should always add these controllers
        // as processes to the job, but refer to the TODO in the Job code around standard
        // input handling).
        $stdin_pipe->attachStdinEndpoint(PipeDefaults::$stdinFormat);
        
      } elseif ($data['data'] === 'background') {
        $job->setForeground(false);
      } elseif ($data['data'] === 'expression') {
        $job->setForeground(true);
        
        // For expressions, we have to create an endpoint which we can later
        // read objects from.
        $capture_endpoint = $stdout_pipe->createOutboundEndpoint(PipeDefaults::$stdoutFormat);
        
      } else {
        throw new Exception('Unknown type of job in pipeline '.print_r($data, true));
      }
      
      omni_trace("executing job");
    } catch (Exception $ex) {
      $stdin_pipe->killController();
      $stdout_pipe->killController();
      $stderr_pipe->killController();
      throw $ex;
    }
    
    try {
      $job->execute(
        $shell,
        $stdin_pipe,
        $stdout_pipe,
        $stderr_pipe);
    } catch (Exception $ex) {
      $job->killTemporaryPipes();
      throw $ex;
    }
    
    $job->untrackTemporaryPipes();
    
    if ($data['data'] === 'expression') {
      omni_trace("reading data from stdout endpoint for expression pipeline");
      
      $result = array();
      while (true) {
        try {
          $result[] = $capture_endpoint->read();
        } catch (NativePipeClosedException $ex) {
          break;
        }
      }
      
      omni_trace("returning result of stdout from pipeline");
      
      if (count($result) === 0) {
        return null;
      } else if (count($result) === 1) {
        return $result[0];
      } else {
        return $result;
      }
    } else {
      omni_trace("returning new job object");
      return $job;
    }
  }
  
}
