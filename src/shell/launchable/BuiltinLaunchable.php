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
  
  public function prepare(Shell $shell, Job $job, PipeInterface $stdin, PipeInterface $stdout, PipeInterface $stderr) {
    return $this->builtin->prepare($shell, $job, $this->arguments, $stdin, $stdout, $stderr);
  }
  
  public function launch(Shell $shell, Job $job, array $prepare_data) {
    try {
      $this->builtin->run($shell, $job, $this->arguments, $prepare_data);
    } catch (Exception $ex) {
      phlog($ex);
      
      // Be on the safe side and close any pipes that were specified in
      // $prepare_data.
      foreach ($prepare_data as $value) {
        if ($value instanceof Endpoint) {
          $value->close();
        }
      }
    }
    
    return null;
  }
  
 }