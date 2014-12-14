<?php

final class NativeLaunchable
  extends Phobject
  implements LaunchableInterface {
  
  private $executable;
  private $argv;
  
  public function __construct($executable, $argv) {
    $this->executable = $executable;
    $this->argv = $argv;
  }
  
  public function launch(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    $stdin_endpoint = $stdin->createOutboundEndpoint(
      Endpoint::FORMAT_BYTE_STREAM,
      $this->executable." stdin");
    $stdout_endpoint = $stdout->createInboundEndpoint(
      Endpoint::FORMAT_BYTE_STREAM,
      $this->executable." stdout");
    $stderr_endpoint = $stderr->createInboundEndpoint(
      Endpoint::FORMAT_BYTE_STREAM,
      $this->executable." stderr");
    
    omni_trace("Launching ".$this->executable.
      " with stdin pipe ".$stdin->getName().
      " with stdout pipe ".$stdout->getName().
      " with stderr pipe ".$stderr->getName());
    
    $pid = pcntl_fork();
    if ($pid === 0) {
      $stdin_endpoint->closeWrite();
      $stdout_endpoint->closeRead();
      $stderr_endpoint->closeRead();
      
      $argv = $this->argv;
      array_unshift($argv, $this->executable);
    
      $shell->launchProcess(
        $argv,
        $job,
        $stdin_endpoint->getReadFD(),
        $stdout_endpoint->getWriteFD(),
        $stderr_endpoint->getWriteFD());
        
      // We never continue execution here because we're replaced
      // with the child process.
    } else if ($pid < 0) {
      // Failed to fork.
      throw new Exception('Failed to fork!');
    }
    
    $stdin_endpoint->closeRead();
    $stdout_endpoint->closeWrite();
    $stderr_endpoint->closeWrite();
    
    return $pid;
  }
  
}