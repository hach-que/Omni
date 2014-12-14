<?php

final class OmniAppLaunchable
  extends Phobject
  implements LaunchableInterface {
  
  public function prepare(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    // TODO Migrate to two stage prepare / launch.
    return array();
  }
  
  public function launch(Shell $shell, Job $job, array $prepare_data) {
    // TODO Migrate to two stage prepare / launch.
    return array();
    
    throw new Exception('not implemented');
  }
  
}