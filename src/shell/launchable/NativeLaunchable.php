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
  
  public function prepare(Shell $shell, Job $job, PipeInterface $stdin, PipeInterface $stdout, PipeInterface $stderr) {
    // Set up all of the endpoints for our launch later on.
    
    $stdin_endpoint = $stdin->createOutboundEndpoint(
      Endpoint::FORMAT_BYTE_STREAM,
      $this->executable." stdin");
    $stdout_endpoint = $stdout->createInboundEndpoint(
      Endpoint::FORMAT_BYTE_STREAM,
      $this->executable." stdout");
    $stderr_endpoint = $stderr->createInboundEndpoint(
      Endpoint::FORMAT_BYTE_STREAM,
      $this->executable." stderr");
      
    return array(
      'stdin' => $stdin_endpoint,
      'stdout' => $stdout_endpoint,
      'stderr' => $stderr_endpoint,
      'stdin_pipe' => $stdin,
      'stdout_pipe' => $stdout,
      'stderr_pipe' => $stderr,
      'close_read_on_fork' => array(
        $stdin_endpoint,
      ),
      'close_write_on_fork' => array(
        $stdout_endpoint,
        $stderr_endpoint,
      ),
    );
  }
  
  public function launch(Shell $shell, Job $job, array $prepare_data) {
    $stdin = idx($prepare_data, 'stdin_pipe');
    $stdout = idx($prepare_data, 'stdout_pipe');
    $stderr = idx($prepare_data, 'stderr_pipe');
    $stdin_endpoint = idx($prepare_data, 'stdin');
    $stdout_endpoint = idx($prepare_data, 'stdout');
    $stderr_endpoint = idx($prepare_data, 'stderr');
    
    omni_trace("Launching ".$this->executable.
      " with stdin pipe ".$stdin->getName().
      " with stdout pipe ".$stdout->getName().
      " with stderr pipe ".$stderr->getName());
    omni_trace("The FDs for ".$this->executable.
      " are stdin: ".$stdin_endpoint->getReadFD().
      " , stdout: ".$stdout_endpoint->getWriteFD().
      " , stderr: ".$stderr_endpoint->getWriteFD());
    
    $pid = pcntl_fork();
    if ($pid === 0) {
      $stdin_endpoint->closeWrite();
      $stdout_endpoint->closeRead();
      $stderr_endpoint->closeRead();
      
      $argv = $this->argv;
      array_unshift($argv, $this->executable);
      
      // Expand array arguments as additional arguments.
      $expanded_args = array();
      foreach ($argv as $arg) {
        if (is_array($arg)) {
          foreach ($arg as $a) {
            $expanded_args[] = (string)$a;
          }
        } else if ($arg === null) {
          // Skip this argument.
        } else {
          $expanded_args[] = (string)$arg;
        }
      }
      
      $shell->launchProcess(
        $expanded_args,
        $job,
        FileDescriptorManager::getNativeFD($stdin_endpoint->getReadFD()),
        FileDescriptorManager::getNativeFD($stdout_endpoint->getWriteFD()),
        FileDescriptorManager::getNativeFD($stderr_endpoint->getWriteFD()));
        
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