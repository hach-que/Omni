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
    
    $stdout_endpoint = $stdout->createInboundEndpoint(null, "futures builtin stdout");
    
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
    foreach ($parser->getArg('futures') as $key => $future_arg) {
      if ($future_arg instanceof Future) {
        $futures[$key] = $future_arg;
      } else if (is_array($future_arg)) {
        foreach ($future_arg as $subkey => $future) {
          if ($future instanceof Future) {
            $futures[$subkey] = $future;
          }
        }
      }
    }
    
    $iterator = new FutureIterator($futures);
    
    // TODO: Make this configurable (but safe for now)
    $iterator->limit(8);
    
    $stdout->write($iterator);
    
    $stdout->closeWrite();
  }

}