<?php

final class FuturesBuiltin extends Builtin {

  public function useInProcessPipes() {
    return true;
  }
  
  public function getName() {
    return 'futures';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(null, "futures builtin stdout"),
    );
  }
  
  public function getDescription() {
    return <<<EOF
Accepts a list of arguments which are either futures or arrays
of futures, and returns a future iterator.
EOF;
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(
      array(
        'name' => 'futures',
        'wildcard' => true,
        'help' => 
          'Futures or arrays of futures.'
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
    
    $futures = array();
    foreach ($parser->getArg('futures') as $future_arg) {
      if ($future_arg instanceof Future) {
        $futures[] = $future_arg;
      } else if (is_array($future_arg)) {
        foreach ($future_arg as $future) {
          if ($future instanceof Future) {
            $futures[] = $future;
          }
        }
      }
    }
    
    $stdout->write(new FutureIterator($futures));
    
    $stdout->closeWrite();
  }

}