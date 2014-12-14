<?php

interface LaunchableInterface {
  
  public function prepare(Shell $shell, Job $job, Pipe $stdin, Pipe $stdout, Pipe $stderr);
  
  public function launch(Shell $shell, Job $job, array $prepare_data);
  
}