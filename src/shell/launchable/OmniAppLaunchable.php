<?php

final class OmniAppLaunchable
  extends Phobject
  implements LaunchableInterface {
  
  private $executable;
  private $argv;
  
  public function __construct($executable, $argv) {
    $this->executable = $executable;
    $this->argv = $argv;
  }
  
  public function prepare(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    $stdin_endpoint = $stdin->createOutboundEndpoint(
      Endpoint::FORMAT_PHP_SERIALIZATION,
      $this->executable." stdin");
    $stdout_endpoint = $stdout->createInboundEndpoint(
      Endpoint::FORMAT_PHP_SERIALIZATION,
      $this->executable." stdout");
    $stderr_endpoint = $stderr->createInboundEndpoint(
      Endpoint::FORMAT_PHP_SERIALIZATION,
      $this->executable." stderr");
      
    return array(
      'stdin' => $stdin_endpoint,
      'stdout' => $stdout_endpoint,
      'stderr' => $stderr_endpoint,
    );
  }
  
  public function launch(Shell $shell, Job $job, array $prepare_data) {
    $stdin = idx($prepare_data, 'stdin');
    $stdout = idx($prepare_data, 'stdout');
    $stderr = idx($prepare_data, 'stderr');
    
    $pid = pcntl_fork();
    if ($pid === 0) {
      $stdin->closeWrite();
      $stdout->closeRead();
      $stderr->closeRead();
      
      FileDescriptorManager::replaceStandardPipes(
        $stdin->getReadFD(),
        $stdout->getWriteFD(),
        $stderr->getWriteFD());
        
      PipeDefaults::$stdinFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
      PipeDefaults::$stdoutFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
      PipeDefaults::$stderrFormat = Endpoint::FORMAT_PHP_SERIALIZATION;
      
      $shell->launchScript(
        $job,
        $this->executable,
        $this->argv,
        $stdin,
        $stdout,
        $stderr);
      
      // launchScript() is expected to handle the exit code.
      exit(1);
    } else if ($pid < 0) {
      // Failed to fork.
      throw new Exception('Failed to fork!');
    }
    
    $stdin->closeRead();
    $stdout->closeWrite();
    $stderr->closeWrite();
    
    return $pid;
  }
  
}