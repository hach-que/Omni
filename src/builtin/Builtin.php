<?php

abstract class Builtin extends Phobject {

  abstract function getName();
  
  abstract function getArguments(Shell $shell, Job $job, array $prepare_data);
  
  abstract function prepare(Shell $shell, Job $job, array $arguments, PipeInterface $stdin, PipeInterface $stdout, PipeInterface $stderr);
  
  abstract function run(Shell $shell, Job $job, array $arguments, array $prepare_data);
  
  public function useInProcessPipes() {
    return false;
  }
  
}
