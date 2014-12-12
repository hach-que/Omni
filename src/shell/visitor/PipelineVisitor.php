<?php

final class PipelineVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    $job = new Job();
    foreach ($data['children'] as $child) {
      $job->addStage($this->visitChild($shell, $child));
    }
    
    $job->execute(
      $shell,
      $shell->getStdinPipe(),
      $shell->getStdoutPipe(),
      $shell->getStderrPipe());
    
    return $job;
  }
  
}
