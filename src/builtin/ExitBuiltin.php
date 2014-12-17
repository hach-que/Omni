<?php

final class ExitBuiltin extends Builtin {

  public function getName() {
    return 'exit';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    Pipe $stdin,
    Pipe $stdout,
    Pipe $stderr) {
    
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