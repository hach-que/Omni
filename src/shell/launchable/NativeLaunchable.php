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
  
  public function launch(Shell $shell, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    $native_stdin_pipe = omni_pipe();
    $native_stdout_pipe = omni_pipe();
    $native_stderr_pipe = omni_pipe();
    
    $stdin_stream = new OutboundFDStream($native_stdin_pipe['write']);
    $stdout_stream = new InboundFDStream($native_stdout_pipe['read']);
    $stderr_stream = new InboundFDStream($native_stderr_pipe['read']);
    
    $pid = pcntl_fork();
    if ($pid === 0) {
      $argv = $this->argv;
      array_unshift($argv, $this->executable);
      $shell->launchProcess(
        $this->argv,
        $native_stdin_pipe['read'],
        $native_stdout_pipe['write'],
        $native_stderr_pipe['write'],
        true /* TODO */);
        
      // We never continue execution here because we're replaced
      // with the child process.
    } else if ($pid < 0) {
      // Failed to fork.
      throw new Exception('Failed to fork!');
    } else {
      // TODO Set up process group appropriately.
    }
    
    $stdin->attachOutbound($stdin_stream);
    $stdout->attachInbound($stdout_stream);
    $stderr->attachInbound($stderr_stream);
  }
  
}