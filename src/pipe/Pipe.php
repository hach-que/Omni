<?php

/**
 * An abstraction on top of the native system pipes, which allows multiple inputs
 * and outputs, and permits complex objects to be transmitted over the stream
 * (via serialization).
 */
final class Pipe extends Phobject {

  const DIST_METHOD_ROUND_ROBIN = 'round-robin';
  const DIST_METHOD_SPLIT = 'split';
  
  private $targetType = null;
  private $distributionMethod = self::DIST_METHOD_ROUND_ROBIN;
  private $inboundEndpoints = array();
  private $outboundEndpoints = array();
  private $controllerPid = null;
  private $roundRobinCounter = 0;
  private $defaultInboundFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
  private $defaultOutboundFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
  
  public function getName() {
    $inbound_names = mpull($this->inboundEndpoints, 'getName');
    $outbound_names = mpull($this->outboundEndpoints, 'getName');
    $inbound_implode = implode(',', $inbound_names);
    $outbound_implode = implode(',', $outbound_names);
    
    return "[in:(".$inbound_implode.");out:(".$outbound_implode.")]";
  }
  
  public function setTypeConversion($target_type) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    $this->targetType = $target_type;
    return $this;
  }
  
  public function setDistributionMethod($dist_method) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    $this->distributionMethod = $dist_method;
    return $this;
  }
  
  public function setDefaultInboundFormat($format) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    $this->defaultInboundFormat = $format;
    return $this;
  }
  
  public function setDefaultOutboundFormat($format) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    $this->defaultOutboundFormat = $format;
    return $this;
  }
  
  /**
   * Creates and returns an inbound endpoint.  An inbound endpoint
   * is one that is written to in order to push data into this
   * pipe.
   */
  public function createInboundEndpoint($format = null, $name = null) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    if ($format === null) {
      $format = $this->defaultInboundFormat;
    }
    
    $endpoint = id(new Endpoint())
      ->setName($name)
      ->setReadFormat($format)
      ->setWriteFormat($format);
    $this->inboundEndpoints[] = $endpoint;
    return $endpoint;
  }
  
  /**
   * Creates and returns an outbound endpoint.  An outbound endpoint
   * is one that is read from in order to pull data from this
   * pipe.
   */
  public function createOutboundEndpoint($format = null, $name = null) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    if ($format === null) {
      $format = $this->defaultOutboundFormat;
    }
    
    $endpoint = id(new Endpoint())
      ->setName($name)
      ->setReadFormat($format)
      ->setWriteFormat($format);
    $this->outboundEndpoints[] = $endpoint;
    return $endpoint;
  }
  
  /**
   * Attaches FD 0 (stdin) as an inbound endpoint to this pipe.
   */
  public function attachStdinEndpoint($format = null) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    if ($format === null) {
      $format = $this->defaultInboundFormat;
    }
    
    $endpoint = id(new Endpoint(array('read' => Shell::STDIN_FILENO, 'write' => null)))
      ->setName("stdin")
      ->setReadFormat($format)
      ->setWriteFormat($format)
      ->setClosable(false);
    $this->inboundEndpoints[] = $endpoint;
    return $endpoint;
  }
  
  /**
   * Attaches FD 1 (stdout) as an outbound endpoint to this pipe.
   */
  public function attachStdoutEndpoint($format = null) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    if ($format === null) {
      $format = $this->defaultOutboundFormat;
    }
    
    $endpoint = id(new Endpoint(array('read' => null, 'write' => Shell::STDOUT_FILENO)))
      ->setName("stdout")
      ->setReadFormat($format)
      ->setWriteFormat($format)
      ->setClosable(false);
    $this->outboundEndpoints[] = $endpoint;
    return $endpoint;
  }
  
  /**
   * Attaches FD 2 (stderr) as an outbound endpoint to this pipe.
   */
  public function attachStderrEndpoint($format = null) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe is immutable; controller has been started.');
    }
  
    if ($format === null) {
      $format = $this->defaultOutboundFormat;
    }
    
    $endpoint = id(new Endpoint(array('read' => null, 'write' => Shell::STDERR_FILENO)))
      ->setName("stderr")
      ->setReadFormat($format)
      ->setWriteFormat($format)
      ->setClosable(false);
    $this->outboundEndpoints[] = $endpoint;
    return $endpoint;
  }

  /**
   * Performs a single update step, taking objects from the
   * inbound streams and directing them to the outbound streams.
   */
  public function update() {
    if (count($this->outboundEndpoints) === 0) {
      throw new Exception(
        'This pipe has no outbound endpoints configured and thus '.
        'can not send data anywhere.');
    }
    
    $temporary = array();
    
    $inbound_fds = array();
    $outbound_fds = array();
    $except_fds = array();
    foreach ($this->inboundEndpoints as $key => $endpoint) {
      $inbound_fds[$key] = $endpoint->getReadFD();
      $except_fds[$key] = $endpoint->getReadFD();
    }
    
    if (count($inbound_fds) === 0) {
      // No further update() calls will result in more activity.
      return false;
    }
    
    $result = fd_select($inbound_fds, $outbound_fds, $except_fds);
    $streams_ready = idx($result, 'ready');
    $inbound_fds = idx($result, 'read');
    $outbound_fds = idx($result, 'write');
    $except_fds = idx($result, 'except');
    
    omni_trace("!!! select returned with ".$streams_ready." ready streams");
    if ($streams_ready === 0) {
      omni_trace("WARNING: SELECT RETURNING WITH NO READY STREAMS!");
    }
    
    // Read objects from all the streams that are
    // ready for reading.
    foreach ($this->inboundEndpoints as $key => $endpoint) {
      if (idx($inbound_fds, $endpoint->getReadFD(), false)) {
        $converter = new TypeConverter();
        
        try {
          $object = $endpoint->read();
        } catch (EIOWhileReadingStdinException $ex) {
          // We got EIO while specifically reading from FD 0.  This
          // indicates that this controller is attempting to read from
          // standard input, but is currently in a background job.
          // In this scenario, select() still reports there's data
          // available on stdin, but we don't have permission to actually
          // read the data.  There's nothing we can do unless the user
          // brings this job back into the foreground, so we just sleep
          // a little while and then continue.
          usleep(5000);
          continue;
        } catch (NativePipeClosedException $ex) {
          // Unable to read any more data from this stream.
          $endpoint->close();
          unset($this->inboundEndpoints[$key]);
          continue;
        }
        
        $type = $converter->getType($object);
        
        if ($this->targetType !== null) {
          $type = $this->targetType;
          $object = $converter->convert($object, $type);
        }
        
        $temporary[] = $object;
      }
    }
    
    if (count($temporary) === 0) {
      // We have no objects.
      
      // TODO: We shouldn't really be able to get here, given that
      // fd_select told us we can read data from one of the inbound
      // endpoints.  Should this actually be an error?
      return true;
    }
    
    switch ($this->distributionMethod) {
      case self::DIST_METHOD_SPLIT:
        $total_objects = count($temporary);
        $total_writers = count($this->outboundEndpoints);
        $per_writer = (int)floor($total_objects / $total_writers);
        $remaining = $total_objects % $total_writers;
        $i = 0;
        foreach ($this->outboundEndpoints as $key => $outbound) {
          $x = 0;
          if ($key === last_key($this->outboundEndpoints)) {
            $x = $remaining;
          }
          
          $selected = array_slice($temporary, $i, $i + $per_writer + $x);
          foreach ($selected as $obj) {
            echo "Dispatching object to outbound endpoint ".$outbound->getName()."\n";
            $outbound->write($obj);
          }
          $i += $per_writer;
        }
        break;
      case self::DIST_METHOD_ROUND_ROBIN:
        $total_writers = count($this->outboundEndpoints);
        foreach ($temporary as $obj) {
          $this->outboundEndpoints[$this->roundRobinCounter++]->write($obj);
          $this->roundRobinCounter = $this->roundRobinCounter % $total_writers;
        }
        break;
    }
    
    return true;
  }
  
  /*
  public function controllerReceivedSIGTERM() {
    omni_trace("controller got SIGTERM!");
  
    // Close off all endpoints.
    foreach ($this->inboundEndpoints as $endpoint) {
      $endpoint->close();
    }
    foreach ($this->outboundEndpoints as $endpoint) {
      $endpoint->close();
    }
    
    // Then exit.
    omni_exit(0);
  }
  */
  
  public function isValid() {
    return count($this->inboundEndpoints) > 0 && count($this->outboundEndpoints) > 0;
  }
  
  public function startController(Shell $shell, Job $job, $report_untracked = false) {
    if ($this->controllerPid !== null) {
      throw new Exception('Pipe controller has already been started!');
    }
  
    omni_trace("starting pipe controller");
    
    omni_trace("instantiating native pipes for endpoints");
    foreach ($this->inboundEndpoints as $endpoint) {
      $endpoint->instantiatePipe();
    }
    foreach ($this->outboundEndpoints as $endpoint) {
      $endpoint->instantiatePipe();
    }
  
    omni_trace("forking omni for pipe controller");
  
    $pid = pcntl_fork();
    if ($pid === 0) {
      omni_trace("i am the child pipe controller");
      
      $shell->prepareForkedProcess($job, true);
      
      omni_trace("setting SIGTTIN to SIG_IGN");
      
      pcntl_signal(SIGTTIN, SIG_IGN);
      
      //omni_trace("registering SIGTERM");
      
      //pcntl_signal(SIGTERM, SIG_DFL);
      //pcntl_signal(SIGTERM, array($this, 'controllerReceivedSIGTERM'));
      
      omni_trace("pipe ".posix_getpid().": ".$this->getName());
      
      omni_trace("closing opposite endpoints for child");
      
      // We have to close the other ends of the endpoints
      // when in the child process, so that end-of-pipe
      // signals work correctly.
      foreach ($this->inboundEndpoints as $endpoint) {
        $endpoint->closeWrite();
      }
      foreach ($this->outboundEndpoints as $endpoint) {
        $endpoint->closeRead();
      }
      
      omni_trace("updating pipe controller forever");
      
      // This is the child process.
      while ($this->update()) {
        // Repeat while update returns true.
      }
      
      omni_trace("all inputs exhausted");
      
      omni_trace("closing all endpoints for child");
      
      // Close all outbound and inbound endpoints, to ensure
      // that anyone listening on any of the system pipes
      // knows that all the data has been sent.
      foreach ($this->inboundEndpoints as $endpoint) {
        $endpoint->closeRead();
      }
      foreach ($this->outboundEndpoints as $endpoint) {
        $endpoint->closeWrite();
      }
      
      omni_trace("pipe controller is now exiting");
      
      omni_exit(0);
    } elseif ($pid > 0) {
      omni_trace("i am the parent process, with child pid $pid");
      
      omni_trace("setting pipe controller pid");
      
      // Parent process; set the controller PID to
      // prevent other operations from changing the pipe.
      $this->controllerPid = $pid;
      
      omni_trace("closing opposite endpoints for parent");
      
      // We have to close the other ends of the endpoints
      // when in the parent process, so that end-of-pipe
      // signals work correctly.
      foreach ($this->inboundEndpoints as $endpoint) {
        $endpoint->closeRead();
      }
      foreach ($this->outboundEndpoints as $endpoint) {
        $endpoint->closeWrite();
      }
      
      omni_trace("returning process wrapper");
      
      // Return the new pipe process inside the process
      // wrapper.
      $process = new ProcessIDWrapper($pid, 'pipe', $this->getName());
      if ($report_untracked) {
        // TODO: Use a better flag here?
        $process->setCompleted(true);
      }
      return $process;
    } else {
      // Unable to start controller.
      throw new Exception('Unable to start pipe controller!');
    }
  }
  
  public function killController() {
    if ($this->controllerPid === null) {
      return;
    }
  
    omni_trace("terminating controller with pid ".$this->controllerPid);
    
    posix_kill($this->controllerPid, SIGTERM);
    
    omni_trace("waiting for controller with pid ".$this->controllerPid." to finish up...");
    
    $status = '';
    $pid = pcntl_waitpid($this->controllerPid, $status, 0);
    if ($pid === $this->controllerPid) {
      omni_trace("got status $status after waitpid returned from controller");
    } else {
      omni_trace("got status $status after waitpid returned FROM UNKNOWN PROCESS");
    } 
    
    if (pcntl_wifstopped($status)) {
      omni_trace("controller process is STOPPED");
    }
    if (pcntl_wifexited($status)) {
      omni_trace("controller process is EXITED");
    }
    if (pcntl_wifsignaled($status)) {
      omni_trace("controller process is SIGNALED");
      omni_trace("controller process got signal ".pcntl_wtermsig($status));
    }
    
    omni_trace("assuming controller has exited....");
  }
  
}