<?php

abstract class Builtin extends Phobject {

  abstract function getName();

  abstract function run(Shell $shell, array $arguments, Pipe $stdin, Pipe $stdout, Pipe $stderr);
  
}
