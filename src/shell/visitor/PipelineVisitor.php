<?php

final class PipelineVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    $processes = array();
    foreach ($data['children'] as $child) {
      $processes[] = $this->visitChild($shell, $child);
    }
    
    $job = new Job();
    $job->processes = $processes;
    $job->stdin = Shell::STDIN_FILENO;
    $job->stdout = Shell::STDOUT_FILENO;
    $job->stderr = Shell::STDERR_FILENO;
    
    $shell->launchJob($job);
    
    return null;
  }
  
}
