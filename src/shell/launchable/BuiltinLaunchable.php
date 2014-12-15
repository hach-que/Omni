<?php

final class BuiltinLaunchable
  extends Phobject
  implements LaunchableInterface {
  
  private $builtin;
  private $arguments;

  public function __construct(Builtin $builtin, $arguments) {
    $this->builtin = $builtin;
    $this->arguments = $arguments;
    array_unshift($this->arguments, $this->builtin->getName());
  }
  
  public function prepare(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    return $this->builtin->prepare($shell, $job, $this->arguments, $stdin, $stdout, $stderr);
  }
  
  public function launch(Shell $shell, Job $job, array $prepare_data) {
    $this->builtin->run($shell, $job, $this->arguments, $prepare_data);
    return null;
  }
  
 }