<?php

final class EchoBuiltin extends Builtin {

  public function getName() {
    return 'echo';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(null, "echo stdout"),
    );
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array();
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    
    if (count($arguments) === 1) {
      $stdout->write("\n");
    }
    
    for ($i = 1; $i < count($arguments); $i++) {
      $stdout->write($arguments[$i]);
    }
    
    $stdout->closeWrite();
  }

}