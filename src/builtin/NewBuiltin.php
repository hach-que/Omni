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
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(null, "new builtin stdout"),
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