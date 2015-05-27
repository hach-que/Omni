<?php

final class NewBuiltin extends Builtin {

  public function useInProcessPipes() {
    return true;
  }
  
  public function getName() {
    return 'new';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    $stdout_endpoint = $stdout->createInboundEndpoint(null, "new builtin stdout");
    
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
Creates new objects in Omni.
EOF;
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(
      array(
        'name' => 'type',
        'short' => 't',
        'param' => 'type',
        'help' => 
          'The type to create.'
      ),
      array(
        'name' => 'args',
        'wildcard' => true,
        'help' => 
          'The constructor arguments.'
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
    
    if ($parser->getArg('type')) {
      $type_name = $parser->getArg('type');
      
      if ($type_name === 'File') {
        // TODO We should probably use the VFS layer here...
        $type_name = 'RealStructuredFile';
      }
      
      $stdout->write(newv($type_name, $parser->getArg('args')));
    } else {
      throw new Exception('The type argument is mandatory.');
    }
    
    $stdout->closeWrite();
  }

}