<?php

class Pipe extends Phobject {

  const DIST_METHOD_ROUND_ROBIN = 'round-robin';
  const DIST_METHOD_SPLIT = 'split';
  
  private $targetType = null;
  private $distributionMethod = self::DIST_METHOD_ROUND_ROBIN;
  private $inboundEndpoints = array();
  private $outboundEndpoints = array();
  private $controllerPid = null;
  private $controllerControlPipe = null;
  private $controllerDataPipe = null;
  private $roundRobinCounter = 0;
  private $defaultInboundFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
  private $defaultOutboundFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
  private $hasPreviouslyHadInboundEndpoints = false;
  private $hasPreviouslyHadOutboundEndpoints = false;
  private $objectsPendingConnectionOfOutboundEndpoints = array();
  private $shell = null;
  private $job = null;
  
  /**
   * When pipes are being used as part of the shell, we need to
   * call prepareForkedProcess on the shell to restore process
   * defaults.
   */
  public function setShellAndJob(Shell $shell, Job $job) {
    $this->shell = $shell;
    $this->job = $job;
    return $this;
  }
  
  public function setTypeConversion($target_type) {
    $this->startControllerIfNotRunning();
    
    if ($this->controllerPid === posix_getpid()) {
      $this->targetType = $target_type;
    } else {
      $this->dispatchControlSelfCallEvent(__FUNCTION__, $target_type);
    }
    
    return $this;
  }
  
  public function setDistributionMethod($dist_method) {
    $this->startControllerIfNotRunning();
    
    if ($this->controllerPid === posix_getpid()) {
      $this->distributionMethod = $dist_method;
    } else {
      $this->dispatchControlSelfCallEvent(__FUNCTION__, $dist_method);
    }
    
    return $this;
  }
  
  public function setDefaultInboundFormat($format) {
    $this->startControllerIfNotRunning();
    
    if ($this->controllerPid === posix_getpid()) {
      $this->defaultInboundFormat = $format;
    } else {
      $this->dispatchControlSelfCallEvent(__FUNCTION__, $format);
    }
    
    return $this;
  }
  
  public function setDefaultOutboundFormat($format) {
    $this->startControllerIfNotRunning();
    
    if ($this->controllerPid === posix_getpid()) {
      $this->defaultOutboundFormat = $format;
    } else {
      $this->dispatchControlSelfCallEvent(__FUNCTION__, $format);
    }
    
    return $this;
  }
  
  public function markFinalized() {
    $this->startControllerIfNotRunning();
    
    if ($this->controllerPid === posix_getpid()) {
      $this->hasPreviouslyHadInboundEndpoints = true;
      $this->hasPreviouslyHadOutboundEndpoints = true;
    } else {
      $this->dispatchControlSelfCallEvent(__FUNCTION__);
    }
    
    return $this;
  }
  
  public function getControllerProcess() {
    $this->startControllerIfNotRunning();
    
    return new ProcessIDWrapper($this->controllerPid, 'pipe', $this->getName());
  }
  
  /**
   * Creates and returns an inbound endpoint.  An inbound endpoint
   * is one that is written to in order to push data into this
   * pipe.
   */
  public function createInboundEndpoint($format = null, $name = null) {
    $this->startControllerIfNotRunning();
    
    if ($format === null) {
      $format = $this->defaultInboundFormat;
    }
    
    // Create the endpoint and instantiate the native pipe
    // underneath.
    $endpoint = id(new Endpoint())
      ->setOwnerPipe($this)
      ->setOwnerType('inbound')
      ->setName($name)
      ->setReadFormat($format)
      ->setWriteFormat($format);
    $endpoint->instantiatePipe();
    $idx = array_push($this->inboundEndpoints, $endpoint) - 1;
    $endpoint->setOwnerIndex($idx);
    
    // Send control event to create inbound endpoint.
    $this->controllerDataPipe->write(array(
      'type' => 'create-endpoint',
      'endpoint-type' => 'inbound',
      'name' => $name,
      'read-format' => $format,
      'write-format' => $format,
      'index' => $idx,
      'closable' => true,
    ));
    FileDescriptorManager::sendFD(
      $this->controllerControlPipe['write'],
      $endpoint->getReadFD());
    $endpoint->closeRead();
      
    return $endpoint;
  }
  
  /**
   * Creates and returns an outbound endpoint.  An outbound endpoint
   * is one that is read from in order to pull data from this
   * pipe.
   */
  public function createOutboundEndpoint($format = null, $name = null) {
    $this->startControllerIfNotRunning();
    
    if ($format === null) {
      $format = $this->defaultOutboundFormat;
    }
    
    // Create the endpoint and instantiate the native pipe
    // underneath.
    $endpoint = id(new Endpoint())
      ->setOwnerPipe($this)
      ->setOwnerType('outbound')
      ->setName($name)
      ->setReadFormat($format)
      ->setWriteFormat($format);
    $endpoint->instantiatePipe();
    $idx = array_push($this->outboundEndpoints, $endpoint) - 1;
    $endpoint->setOwnerIndex($idx);
    
    // Send control event to create inbound endpoint.
    $this->controllerDataPipe->write(array(
      'type' => 'create-endpoint',
      'endpoint-type' => 'outbound',
      'name' => $name,
      'read-format' => $format,
      'write-format' => $format,
      'index' => $idx,
      'closable' => true,
    ));
    FileDescriptorManager::sendFD(
      $this->controllerControlPipe['write'],
      $endpoint->getWriteFD());
    $endpoint->closeWrite();
      
    return $endpoint;
  }
  
  /**
   * Attaches FD 0 (stdin) as an inbound endpoint to this pipe.
   */
  public function attachStdinEndpoint($format = null, $name = null) {
    $this->startControllerIfNotRunning();
    
    if ($format === null) {
      $format = $this->defaultInboundFormat;
    }
    
    if ($name === null) {
      $name = 'stdin';
    }
    
    // Create the endpoint and instantiate the native pipe
    // underneath.
    $endpoint = id(new Endpoint(array('read' => FileDescriptorManager::STDIN_FILENO, 'write' => null)))
      ->setOwnerPipe($this)
      ->setOwnerType('inbound')
      ->setName($name)
      ->setReadFormat($format)
      ->setWriteFormat($format)
      ->setClosable(false);
    $endpoint->instantiatePipe();
    $idx = array_push($this->inboundEndpoints, $endpoint);
    $endpoint->setOwnerIndex($idx);
    
    // Send control event to create inbound endpoint.
    $this->controllerDataPipe->write(array(
      'type' => 'create-endpoint',
      'endpoint-type' => 'inbound',
      'name' => $name,
      'read-format' => $format,
      'write-format' => $format,
      'index' => $idx,
      'closable' => false,
    ));
    FileDescriptorManager::sendFD(
      $this->controllerControlPipe['write'],
      $endpoint->getReadFD());
      
    return $endpoint;
  }
  
  /**
   * Attaches FD 1 (stdout) as an outbound endpoint to this pipe.
   */
  public function attachStdoutEndpoint($format = null, $name = null) {
    $this->startControllerIfNotRunning();
    
    if ($format === null) {
      $format = $this->defaultOutboundFormat;
    }
    
    if ($name === null) {
      $name = 'stdout';
    }
    
    // Create the endpoint and instantiate the native pipe
    // underneath.
    $endpoint = id(new Endpoint(array('read' => null, 'write' => FileDescriptorManager::STDOUT_FILENO)))
      ->setOwnerPipe($this)
      ->setOwnerType('outbound')
      ->setName($name)
      ->setReadFormat($format)
      ->setWriteFormat($format);
    $endpoint->instantiatePipe();
    $idx = array_push($this->outboundEndpoints, $endpoint);
    $endpoint->setOwnerIndex($idx);
    
    // Send control event to create inbound endpoint.
    $this->controllerDataPipe->write(array(
      'type' => 'create-endpoint',
      'endpoint-type' => 'outbound',
      'name' => $name,
      'read-format' => $format,
      'write-format' => $format,
      'index' => $idx,
      'closable' => false,
    ));
    FileDescriptorManager::sendFD(
      $this->controllerControlPipe['write'],
      $endpoint->getWriteFD());
      
    return $endpoint;
  }
  
  /**
   * Attaches FD 2 (stderr) as an outbound endpoint to this pipe.
   */
  public function attachStderrEndpoint($format = null, $name = null) {
    $this->startControllerIfNotRunning();
    
    if ($format === null) {
      $format = $this->defaultOutboundFormat;
    }
    
    if ($name === null) {
      $name = 'stderr';
    }
    
    // Create the endpoint and instantiate the native pipe
    // underneath.
    $endpoint = id(new Endpoint(array('read' => null, 'write' => FileDescriptorManager::STDERR_FILENO)))
      ->setOwnerPipe($this)
      ->setOwnerType('outbound')
      ->setName($name)
      ->setReadFormat($format)
      ->setWriteFormat($format);
    $endpoint->instantiatePipe();
    $idx = array_push($this->outboundEndpoints, $endpoint);
    $endpoint->setOwnerIndex($idx);
    
    // Send control event to create inbound endpoint.
    $this->controllerDataPipe->write(array(
      'type' => 'create-endpoint',
      'endpoint-type' => 'outbound',
      'name' => $name,
      'read-format' => $format,
      'write-format' => $format,
      'index' => $idx,
      'closable' => false,
    ));
    FileDescriptorManager::sendFD(
      $this->controllerControlPipe['write'],
      $endpoint->getWriteFD());
      
    return $endpoint;
  }
  
  public function getName() {
    $inbound_names = mpull($this->inboundEndpoints, 'getName');
    $outbound_names = mpull($this->outboundEndpoints, 'getName');
    $inbound_implode = implode(',', $inbound_names);
    $outbound_implode = implode(',', $outbound_names);
    
    return "[in:(".$inbound_implode.");out:(".$outbound_implode.")]";
  }

  public function hasEndpoints() {
    return count($this->inboundEndpoints) > 0 && count($this->outboundEndpoints) > 0;
  }
  
  /**
   * Constructs a "self call" event to the controller.
   */
  private function dispatchControlSelfCallEvent($function_name) {
    $msg = array(
      'type' => 'self-call',
      'function' => $function_name,
      'argv' => func_get_args(),
    );
    $this->controllerDataPipe->write($msg);
  }
  
  /**
   * Constructs an "endpoint call" event to the controller.
   */
  public function dispatchControlEndpointCallEvent($index, $type, $function_name, $argv) {
    $msg = array(
      'type' => 'endpoint-call',
      'index' => $index,
      'endpoint-type' => $type,
      'function' => $function_name,
      'argv' => $argv,
    );
    $this->controllerDataPipe->write($msg);
  }
  
  /**
   * Handles a control event being sent to the controller process
   * over the control data pipe.  This is used to update the state
   * of the controller process after it has been created by the shell.
   */
  private function handleControlEvent() {
    try {
      $call = $this->controllerDataPipe->read();
    } catch (NativePipeClosedException $ex) {
      // Unable to read any more data from the control stream.
      $this->controllerDataPipe->close();
      FileDescriptorManager::close($this->controllerControlPipe['read']);
      $this->controllerDataPipe = null;
      $this->controllerControlPipe = null;
      return;
    }
    
    switch ($call['type']) {
      case 'self-call':
        call_user_func_array(array($this, $call['function']), $call['argv']);
        break;
      case 'create-endpoint':
        if ($call['endpoint-type'] === 'inbound') {
          $fd_type = 'read';
        } else {
          $fd_type = 'write';
        }
        $fd = FileDescriptorManager::receiveFD(
          $this->controllerControlPipe['read'],
          'native pipe for '.$call['name'],
          $fd_type);
        $pipe = null;
        if ($call['endpoint-type'] === 'inbound') {
          $pipe = array('write' => null, 'read' => $fd);
        } else {
          $pipe = array('write' => $fd, 'read' => null);
        }
        $endpoint = id(new Endpoint($pipe))
          ->setName($call['name'])
          ->setReadFormat($call['read-format'])
          ->setWriteFormat($call['write-format'])
          ->setClosable($call['closable'])
          ->setOwnerPipe($this)
          ->setOwnerType($call['endpoint-type']);
        // We skip setOwnerIndex so that the controller
        // doesn't use remoting.
        if ($call['endpoint-type'] === 'inbound') {
          $this->inboundEndpoints[$call['index']] = $endpoint;
          $this->hasPreviouslyHadInboundEndpoints = true;
        } else {
          $this->outboundEndpoints[$call['index']] = $endpoint;
          $this->hasPreviouslyHadOutboundEndpoints = true;
          $this->sendPendingObjects();
        }
        break;
      case 'endpoint-call':
        if ($call['endpoint-type'] === 'inbound') {
          call_user_func_array(
            array(
              $this->inboundEndpoints[$call['index']],
              $call['function']),
            $call['argv']);
        } else {
          call_user_func_array(
            array(
              $this->outboundEndpoints[$call['index']],
              $call['function']),
            $call['argv']);
        }
        break;
      default:
        omni_trace('unknown control event: '.$call['type']);
        break;
    }
  }
  
  /**
   * Sends any objects that have been accumulated while waiting
   * for an outbound endpoint to be connected.  Since this method
   * will only have an effect after the first endpoint is connected,
   * we can just send all objects to the first, and only, endpoint.
   */
  private function sendPendingObjects() {
    if (count($this->objectsPendingConnectionOfOutboundEndpoints) === 0) {
      return;
    }
    
    if (count($this->outboundEndpoints) === 0) {
      throw new Exception(
        'sendPendingObjects called when no '.
        'outbound endpoints present');
    }
    
    $outbound = head($this->outboundEndpoints);
    foreach ($this->objectsPendingConnectionOfOutboundEndpoints as $object) {
      $outbound->write($object);
    }
    $this->objectsPendingConnectionOfOutboundEndpoints = array();
  }
  
  /**
   * Starts the seperate controller process if it's not
   * currently running.
   */
  private function startControllerIfNotRunning() {
    if ($this->controllerPid !== null) {
      omni_trace(
        "controller is already running as PID ".$this->controllerPid);
      return;
    }
    
    omni_trace("starting runtime pipe controller");
    
    omni_trace("creating native control pipe");
    
    $this->controllerControlPipe = FileDescriptorManager::createControlPipe(
      'control read',
      'control write');
    
    omni_trace("creating native data pipe");
    
    $this->controllerDataPipe = new Endpoint();
    $this->controllerDataPipe->instantiatePipe();
    
    omni_trace("forking omni for pipe controller");
    
    $pid = pcntl_fork();
    if ($pid === 0) {
      omni_trace("i am the child pipe controller");
      
      $this->controllerPid = posix_getpid();
      
      if ($this->shell !== null && $this->job !== null) {
        $this->shell->prepareForkedProcess($this->job, true);
      }
      
      omni_trace("setting SIGTTIN to SIG_IGN");
      
      pcntl_signal(SIGTTIN, SIG_IGN);
      
      omni_trace("pipe ".posix_getpid());
      
      FileDescriptorManager::close(
        $this->controllerControlPipe['write']);
      $this->controllerDataPipe->closeWrite();
      
      omni_trace("updating pipe controller forever");
      
      // This is the child process.
      while ($this->update()) {
        // Repeat while update returns true.
      }
      
      omni_trace("all inputs exhausted");
      
      omni_trace("pipe controller is now exiting");
      
      omni_exit(0);
    } else if ($pid > 0) {
      omni_trace("i am the parent process, with child pid $pid");
      
      omni_trace("closing read ends of native pipes");
      
      FileDescriptorManager::close(
        $this->controllerControlPipe['read']);
      $this->controllerDataPipe->closeRead();
      
      omni_trace("setting pipe controller pid");
      
      $this->controllerPid = $pid;
    } else {
      throw new Exception('Unable to fork for pipe controller.');
    }
  }
  
  public function killController() {
    if ($this->controllerPid !== null) {
      posix_kill($this->controllerPid, SIGKILL);
      $this->controllerPid = null;
    }
  }
  
  /**
   * Performs a single update step in the controller process,
   * taking objects from the inbound streams and directing
   * them to the outbound streams.
   */
  private function update() {
    $temporary = array();
    $inbound_fds = array();
    $outbound_fds = array();
    $except_fds = array();
    
    omni_trace("begin update");
    
    $real_inbound_endpoints = 0;
    foreach ($this->inboundEndpoints as $key => $endpoint) {
      omni_trace("add endpoint read ".$endpoint->getReadFD());
      $inbound_fds[$key] = $endpoint->getReadFD();
      //$except_fds[$key] = $endpoint->getReadFD();
      $real_inbound_endpoints++;
    }
    
    foreach ($this->outboundEndpoints as $key => $endpoint) {
      omni_trace("add endpoint write ".$endpoint->getWriteFD());
      $outbound_fds[$key] = $endpoint->getWriteFD();
    }
    
    if ($this->controllerDataPipe !== null) {
      omni_trace("add controller data pipe");
      $inbound_fds[] = $this->controllerDataPipe->getReadFD();
    }
    
    if ($real_inbound_endpoints === 0) {
      if ($this->hasPreviouslyHadInboundEndpoints) {
        // No further update() calls will result in more activity.
        omni_trace(
          "no inbound endpoints, terminating");
        return false;
      }
    }
    
    if (count($outbound_fds) === 0) {
      if ($this->hasPreviouslyHadOutboundEndpoints) {
        // No further update() calls will result in more activity.
        omni_trace(
          "no outbound endpoints, terminating");
        return false;
      }
    }
    
    $result = FileDescriptorManager::select($inbound_fds, $outbound_fds, $except_fds);
    $streams_ready = idx($result, 'ready');
    $inbound_fds = idx($result, 'read');
    $outbound_fds = idx($result, 'write');
    $except_fds = idx($result, 'except');
    
    omni_trace("!!! select returned with ".$streams_ready." ready streams");
    if ($streams_ready === 0) {
      omni_trace("WARNING: SELECT RETURNING WITH NO READY STREAMS!");
    }
    
    omni_trace("checking for control messages");
    
    // Handle outbound pipe errors first, since this indicates we won't be
    // able to write any data through this outbound endpoint in the future.
    // This is essential for tracking all scenarios e.g. where the standard
    // input pipe controller needs to exit.
    foreach ($this->outboundEndpoints as $key => $endpoint) {
      omni_trace("evaluating ".$endpoint->getWriteFD()."...");
      if (idx($outbound_fds, $endpoint->getWriteFD(), false) === 'error') {
        // Unable to write any more data from this stream.
        $endpoint->close();
        unset($this->outboundEndpoints[$key]);
        
        // If this is the last outbound endpoint, then we return now,
        // because even if we have data to read, we can't send it anywhere.
        if (count($this->outboundEndpoints) === 0) {
          omni_trace(
            "no outbound endpoints, terminating");
          return false;
        }
        
        continue;
      }
    }
    
    // Handle control events next, and don't do anything else
    // until we've handled them all.  We return here so that update()
    // will be called to pull all control events from the data pipe
    // before we handle any actual objects.
    if ($this->controllerDataPipe !== null) {
      if (idx($inbound_fds, $this->controllerDataPipe->getReadFD(), false)) {
        omni_trace("handling control event...");
        $this->handleControlEvent();
        return true;
      }
    }
    
    omni_trace("reading objects");
    
    // Read objects from all the streams that are
    // ready for reading.
    foreach ($this->inboundEndpoints as $key => $endpoint) {
      omni_trace("evaluating ".$endpoint->getReadFD()."...");
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
    
    omni_trace("there are ".count($temporary)." objects to dispatch");
    
    if (count($temporary) === 0) {
      // We have no objects.  This might be because the
      // file descriptors were selected because they've
      // been closed and we just needed to update their
      // status (rather than reading objects from them).
      return true;
    }
    
    if (count($this->outboundEndpoints) === 0) {
      // Place the objects in a buffer for outbound endpoints, to
      // be dispatched when an outbound endpoint is connected.
      foreach ($temporary as $object) {
        $this->objectsPendingConnectionOfOutboundEndpoints[] = $object;
      }
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
            omni_trace("Dispatching object to outbound endpoint ".$outbound->getName()."\n");
            $outbound->write($obj);
          }
          $i += $per_writer;
        }
        break;
      case self::DIST_METHOD_ROUND_ROBIN:
        $compacted_endpoints = array_values($this->outboundEndpoints);
        $total_writers = count($compacted_endpoints);
        foreach ($temporary as $obj) {
          $compacted_endpoints[$this->roundRobinCounter++]->write($obj);
          $this->roundRobinCounter = $this->roundRobinCounter % $total_writers;
        }
        break;
    }
    
    return true;
  }

}