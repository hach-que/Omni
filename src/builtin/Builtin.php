<?php

abstract class Builtin extends Phobject {

  abstract function getName();
  
  abstract function prepare(Shell $shell, Job $job, array $arguments, Pipe $stdin, Pipe $stdout, Pipe $stderr);
  
  abstract function run(Shell $shell, Job $job, array $arguments, array $prepare_data);
  
}
