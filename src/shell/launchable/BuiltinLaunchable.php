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
  
  public function launch(Shell $shell, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    return $this->builtin->run($shell, $this->arguments, $stdin, $stdout, $stderr);
  }
  
 }