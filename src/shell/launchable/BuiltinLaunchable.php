<?php

final class BuiltinLaunchable
  extends Phobject
  implements 
    LaunchableInterface, 
    BuiltinExitCodeInterface {
  
  private $builtin;
  private $arguments;
  private $exitCode;

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
      $this->exitCode = $this->builtin->run($shell, $job, $this->arguments, $prepare_data);
      if ($this->exitCode === null) {
        $this->exitCode = 0;
      }
    } catch (Exception $ex) {
      $this->exitCode = 128 + SIGILL;
      
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
  
  public function getExitCode() {
    return $this->exitCode;
  }
  
 }