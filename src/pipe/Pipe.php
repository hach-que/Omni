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
      $format = Endpoint::FORMAT_PHP_SERIALIZATION;
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
      $format = Endpoint::FORMAT_PHP_SERIALIZATION;
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
      $format = Endpoint::FORMAT_PHP_SERIALIZATION;
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
      $format = Endpoint::FORMAT_PHP_SERIALIZATION;
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
      $format = Endpoint::FORMAT_PHP_SERIALIZATION;
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
    
    // Read objects from all the streams that are
    // ready for reading.
    foreach ($this->inboundEndpoints as $key => $endpoint) {
      if (idx($inbound_fds, $endpoint->getReadFD(), false)) {
        $converter = new TypeConverter();
        
        try {
          $object = $endpoint->read();
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
  
  public function startController(Shell $shell, Job $job) {
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
      
      $shell->putProcessInProcessGroupIfInteractive($job, $pid);
      
      omni_trace("enabling ticks");
      
      declare(ticks=1);
      
      omni_trace("registering SIGTERM");
      
      pcntl_signal(SIGTERM, array($this, 'controllerReceivedSIGTERM'));
      
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
      return new ProcessIDWrapper($pid, 'pipe', $this->getName());
    } else {
      // Unable to start controller.
      throw new Exception('Unable to start pipe controller!');
    }
  }
  
  public function killController() {
    if ($this->controllerPid === null) {
      return;
    }
  
    omni_trace("killing controller with pid ".$this->controllerPid);
    
    posix_kill($this->controllerPid, SIGTERM);
  }
  
}