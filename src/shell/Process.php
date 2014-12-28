<?php

final class Process
  extends Phobject
  implements LaunchableInterface, ProcessInterface {

  private $arguments;
  private $originalArguments;
  private $pid;
  private $status;
  private $stopped;
  private $completed;
  private $type;
  private $description;
  
  public function __construct(array $argv, $original_argv) {
    $this->arguments = $argv;
    $this->originalArguments = $original_argv;
  }
  
  public function hasProcessID() {
    return $this->pid !== null;
  }
  
  public function getProcessID() {
    return $this->pid;
  }
  
  public function getProcessType() {
    return $this->type;
  }
  
  public function getProcessDescription() {
    return $this->description;
  }
  
  public function getProcessStatus() {
    return $this->status;
  }
  
  public function setProcessStatus($status) {
    $this->status = $status;
  }
  
  public function isStopped() {
    return $this->stopped;
  }
  
  public function isCompleted() {
    return $this->completed;
  }
  
  public function setStopped($stopped) {
    return $this->stopped = $stopped;
  }
  
  public function setCompleted($completed) {
    return $this->completed = $completed;
  }
  
  public function prepare(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    $executable = array_shift($this->arguments);
    $resolved_executable = Filesystem::resolveBinary($executable);
    $is_native = false;
    
    $this->description = trim($resolved_executable.' '.$this->originalArguments);
    
    if ($shell->isKnownBuiltin($executable)) {
      $builtin = $shell->lookupBuiltin($executable);
      $target = $this->createBuiltinLaunch($builtin, $this->arguments);
      $this->type = 'builtin';
    } elseif ($resolved_executable === null) {
      throw new Exception('Unable to find \''.$executable.'\' on the current PATH');
    } elseif ($this->isOmniApp($resolved_executable)) {
      $target = $this->createOmniAppLaunch($resolved_executable, $this->arguments);
      $this->type = 'omni-app';
    } else {
      $target = $this->createNativeLaunch($resolved_executable, $this->arguments);
      $this->type = 'native';
    }
    
    $data = $target->prepare($shell, $job, $stdin, $stdout, $stderr);
    
    return array(
      'target' => $target,
      'data' => $data,
    );
  }
  
  public function launch(Shell $shell, Job $job, array $prepare_data) {
    $target = idx($prepare_data, 'target');
    $data = idx($prepare_data, 'data');
    
    $this->pid = $target->launch($shell, $job, $data);
    
    if ($this->pid === null) {
      // Launch did not result in the creation of a new process
      // (e.g. it was a builtin).  The launch ran all of the required
      // logic, so we mark this process as already completed.
      $this->setCompleted(true);
    }
    
    return $this;
  }
  
  private function createBuiltinLaunch(Builtin $builtin, $arguments) {
    return new BuiltinLaunchable($builtin, $arguments);
  }
  
  private function createOmniAppLaunch($resolved_executable, $arguments) {
    return new OmniAppLaunchable($resolved_executable, $arguments);
  }
  
  private function createNativeLaunch($resolved_executable, $arguments) {
    return new NativeLaunchable($resolved_executable, $arguments);
  }
  
  private function isOmniApp($resolved_executable) {
    // Make sure the file is large enough to contain a hashbang.  If it's
    // not, it can't be an Omni app (or realistically any other application, but
    // we leave it to fail with a proper error in that case).
    if (filesize($resolved_executable) < 2) {
      return false;
    }
  
    // Read the first two bytes of the file to see whether it starts with a hashbang.
    $file = fopen($resolved_executable, 'rb');
    $data = fread($file, 2);
    if ($data === '#!') {
      $char = null;
      $target = "";
      while ($char !== "\n") {
        $char = fread($file, 1);
        if ($char !== "\n" && $char !== "\r") {
          $target .= $char;
        }
      }
      
      // We allow "/omni" anywhere in the hashbang, since different scripts might have
      // different paths on their operating system.
      $components = explode(" ", $target);
      if (substr_count($components[0], "/omni") > 0) {
        return true;
      } else {
        return false;
      }
    }
  }
  
}