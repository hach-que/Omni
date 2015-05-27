<?php

final class PipeBuiltin extends Builtin {

  public function useInProcessPipes() {
    return true;
  }
  
  public function getName() {
    return 'pipe';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    $stdout_endpoint = $stdout->createInboundEndpoint(null, "pipe builtin stdout");
    
    return array(
      'stdout' => $stdout_endpoint,
      'close_read_on_fork' => array(),
      'close_write_on_fork' => array(
        $stdout_endpoint,
      ),
    );
  }
  
  public function getDescription() {
    return <<<EOF
Creates or modifies pipes in Omni.  The default operation is
to create a new pipe.
EOF;
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(
      array(
        'name' => 'pipe',
        'short' => 'p',
        'param' => 'pipe',
        'help' => 
          'Modify an existing pipe.  If this is not specified, '.
          'a new pipe is created.'
      ),
      array(
        'name' => 'finalize',
        'short' => 'f',
        'help' => 'Mark the pipe as finalized.'
      ),
    ); 
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull($this->getArguments($shell, $job, $prepare_data));
    
    $pipe = null;
    if ($parser->getArg('pipe') !== null) {
      $pipe = $parser->getArg('pipe');
      if (!($pipe instanceof Pipe)) {
        throw new PhutilArgumentUsageException('--pipe argument must be of type of Pipe');
      }
    } else {
      $pipe = new Pipe();
      $shell->registerExplicitPipe($pipe);
    }
    
    if ($parser->getArg('finalize')) {
      $pipe->markFinalized();
    }
    
    $stdout->write($pipe);
    
    $stdout->closeWrite();
  }

}