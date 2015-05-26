<?php

final class ChainExecVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (!$this->getAllowSideEffects()) {
      throw new EvaluationWouldCauseSideEffectException();
    }
  
    omni_trace("constructing job");
    
    $expression_options = array();
    
    $job = new Job();
    $job->setCommand($data['original']);
    $job->setChainRoot($this->visitChild($shell, $data['children'][0]));
    if (count($data['children']) >= 2) {
      $expression_options = $this->visitChild($shell, $data['children'][1])->getCopy();
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
    
    omni_trace("detecting pure native job?");
    
    if ($data['data'] === 'foreground' && $job->isPureNativeJob($shell)) {
      omni_trace("is pure native job, will launch with no pipes");
      
      $stdin_pipe = new FixedPipe(FileDescriptorManager::STDIN_FILENO, true);
      $stdout_pipe = new FixedPipe(FileDescriptorManager::STDOUT_FILENO, false);
      $stderr_pipe = new FixedPipe(FileDescriptorManager::STDERR_FILENO, false);
      
      $job->setForeground(true);
      
      omni_trace("executing job");
    } else {
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
          $capture_endpoint = $stdout_pipe->createOutboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION);
          
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
    }
    
    try {
      $job->execute(
        $shell,
        $stdin_pipe,
        $stdout_pipe,
        $stderr_pipe,
        $data['data'] === 'expression' && idx($expression_options, '?', false) === false);
    } catch (Exception $ex) {
      $job->killTemporaryPipes();
      throw $ex;
    }
    
    $func_result = null;
    
    if ($data['data'] === 'expression') {
      if (idx($expression_options, '?', false) === true) {
        // The result of the expression capture is the exit code, not the standard output.
        $capture_endpoint->closeRead();
        $func_result = $job->getExitCode();
      } else {
        omni_trace("reading data from stdout endpoint for expression pipeline");
        
        $result = array();
        while (true) {
          try {
            omni_trace("reading next object from captured expression");
            $result[] = $capture_endpoint->read();
            omni_trace("read an object from the captured expression");
          } catch (NativePipeClosedException $ex) {
            break;
          }
        }
        
        omni_trace("clearing ignored set of processes from job");
        $job->clearProcessesIgnoredForCompletion();
        
        omni_trace("waiting for standard output controller process to exit");
        $shell->scheduleJob($job);
        
        omni_trace("returning result of stdout from pipeline");
        
        if (count($result) === 0) {
          omni_trace("no data returned from capture");
          $func_result = null;
        } else if (count($result) === 1) {
          omni_trace("single result returned from capture");
          $func_result = $result[0];
          
          if (idx($expression_options, 'nt', false) === false &&
              idx($expression_options, 'notrim', false) === false) {
            omni_trace("trimming result because it's a string");
            if (is_string($func_result)) {
              $func_result = trim($func_result);
            } else if ($func_result instanceof BytesContainer) {
              $func_result = new BytesContainer(
                trim((string)$func_result));
            }
          }
          
          if (is_string($func_result) || $func_result instanceof BytesContainer) {
            if (idx($expression_options, 's', false) === true ||
                idx($expression_options, 'split', false) === true) {
              omni_trace("splitting result because it's a string");
              if (is_string($func_result)) {
                $func_result = explode(' ', $func_result);
              } else if ($func_result instanceof BytesContainer) {
                $func_result = explode(' ', new BytesContainer(
                  (string)$func_result));
              }
            }
          }
        } else {
          omni_trace("array returned from capture");
          $func_result = $result;
        }
      }
    } else {
      omni_trace("returning new job object");
      $func_result = $job;
    }
    
    $job->closeTemporaryPipes();
    
    $job->untrackTemporaryPipes();
    
    return $func_result;
  }
  
}
