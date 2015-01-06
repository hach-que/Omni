<?php

final class FunctionLaunchable
  extends Phobject
  implements LaunchableInterface {
  
  private $function;
  private $arguments;

  public function __construct(OmniFunction $function, $arguments) {
    $this->function = $function;
    $this->arguments = $arguments;
  }
  
  public function prepare(Shell $shell, Job $job, PipeInterface $stdin, PipeInterface $stdout, PipeInterface $stderr) {
    return array(
      'stdin' => $stdin->createOutboundEndpoint(null, "function stdin"),
      'stdout' => $stdout->createInboundEndpoint(null, "function stdout"),
    );
  }
  
  public function launch(Shell $shell, Job $job, array $prepare_data) {
    $stdin = idx($prepare_data, 'stdin');
    $stdout = idx($prepare_data, 'stdout');
    
    $pid = pcntl_fork();
    if ($pid === 0) {
      $stdin->closeWrite();
      $stdout->closeRead();
      
      $shell->launchPipeFunction(
        $job,
        $this->function,
        $this->arguments,
        $stdin,
        $stdout);
      
      // We never continue execution here because we're replaced
      // with the child process.
    } else if ($pid < 0) {
      // Failed to fork.
      throw new Exception('Failed to fork!');
    }
    
    $stdin->closeRead();
    $stdout->closeWrite();
    
    return $pid;
  }
  
 }