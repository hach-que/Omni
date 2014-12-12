<?php

final class Process
  extends Phobject
  implements LaunchableInterface {

  private $arguments;
  
  public function __construct(array $argv) {
    $this->arguments = $argv;
  }
  
  public function launch(Shell $shell, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    $executable = array_shift($this->arguments);
    $resolved_executable = Filesystem::resolveBinary($executable);
    $is_native = false;
    
    if ($shell->isKnownBuiltin($executable)) {
      $builtin = $shell->lookupBuiltin($executable);
      $target = $this->createBuiltinLaunch($builtin, $this->arguments);
    } elseif ($resolved_executable === null) {
      throw new Exception('Unable to find \''.$executable.'\' on the current PATH');
    } elseif ($this->isOmniApp($resolved_executable)) {
      $target = $this->createOmniAppLaunch($resolved_executable, $this->arguments);
    } else {
      $target = $this->createNativeLaunch($resolved_executable, $this->arguments);
    }
    
    return $target->launch($shell, $stdin, $stdout, $stderr);
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