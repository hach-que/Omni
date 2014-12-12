<?php

interface LaunchableInterface {
  
  public function launch(Shell $shell, Pipe $stdin, Pipe $stdout, Pipe $stderr);
  
}