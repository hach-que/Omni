<?php

interface PipelineOrChainInterface {

  public function getPipelinesRecursively();

  public function prepare(
    Shell $shell,
    Job $job,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr,
    $stdout_is_captured = false);
    
  public function execute(
    Shell $shell,
    Job $job,
    array $prepare_data);
  
}