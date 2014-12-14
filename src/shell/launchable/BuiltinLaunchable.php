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
  
  public function launch(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    $argv = $this->arguments;
    array_unshift($argv, $this->builtin->getName());
    $this->builtin->run($shell, $job, $argv, $stdin, $stdout, $stderr);
  }
  
 }