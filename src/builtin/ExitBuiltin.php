<?php

final class ExitBuiltin extends Builtin {

  public function getName() {
    return 'exit';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    return array(
      'close_read_on_fork' => array(),
      'close_write_on_fork' => array(),
    );
  }
    
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return null;
  }

  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $shell->requestExit();
  }

}