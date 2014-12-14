<?php

final class PipelineVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    omni_trace("constructing job");
    
    $job = new Job();
    foreach ($data['children'] as $child) {
      $job->addStage($this->visitChild($shell, $child));
    }
    
    omni_trace("setting up pipes for stdin / stdout / stderr");
    
    $stdin_pipe = new Pipe();
    $stdin_pipe->attachStdinEndpoint(Endpoint::FORMAT_BYTE_STREAM);
    $stdout_pipe = new Pipe();
    $stdout_pipe->attachStdoutEndpoint(Endpoint::FORMAT_BYTE_STREAM);
    $stderr_pipe = new Pipe();
    $stderr_pipe->attachStderrEndpoint(Endpoint::FORMAT_BYTE_STREAM);
    
    omni_trace("configuring job background / foreground before execution");
    
    if ($data['data'] === 'foreground') {
      $job->setForeground(true);
    } elseif ($data['data'] === 'background') {
      $job->setForeground(false);
    } else {
      throw new Exception('Unknown type of job in pipeline');
    }
    
    omni_trace("executing job");
    
    $job->execute(
      $shell,
      $stdin_pipe,
      $stdout_pipe,
      $stderr_pipe);
    
    omni_trace("returning new job object");
    
    return $job;
  }
  
}
