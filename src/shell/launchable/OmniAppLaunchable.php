<?php

final class OmniAppLaunchable
  extends Phobject
  implements LaunchableInterface {
  
  public function launch(Shell $shell, Pipe $stdin, Pipe $stdout, Pipe $stderr) {
    throw new Exception('not implemented');
  }
  
}