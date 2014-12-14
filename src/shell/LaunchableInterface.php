<?php

interface LaunchableInterface {
  
  public function launch(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr);
  
}