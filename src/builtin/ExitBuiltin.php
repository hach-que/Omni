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
    
    return array();
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