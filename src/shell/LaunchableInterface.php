<?php

interface LaunchableInterface {
  
  public function prepare(Shell $shell, Job $job, PipeInterface $stdin, PipeInterface $stdout, PipeInterface $stderr);
  
  public function launch(Shell $shell, Job $job, array $prepare_data);
  
}