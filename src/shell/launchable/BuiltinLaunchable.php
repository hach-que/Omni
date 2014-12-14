<?php

final class BuiltinLaunchable
  extends Phobject
  implements LaunchableInterface {
  
  private $builtin;
  private $arguments;

  public function __construct(Builtin $builtin, $arguments) {
    $this->builtin = $builtin;
    $this->arguments = $arguments;
  }
  
  public function prepare(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    // TODO Migrate to two stage prepare / launch.
    return array();
  }
  
  public function launch(Shell $shell, Job $job, array $prepare_data) {
    // TODO Migrate to two stage prepare / launch.
    return array();
    
    $argv = $this->arguments;
    array_unshift($argv, $this->builtin->getName());
    $this->builtin->run($shell, $job, $argv, $stdin, $stdout, $stderr);
  }
  
 }