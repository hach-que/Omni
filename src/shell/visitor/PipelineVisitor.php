<?php

final class PipelineVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    omni_trace("constructing job");
    
    $job = new Job();
    $job->setCommand($data['original']);
    foreach ($data['children'] as $child) {
      $job->addStage($this->visitChild($shell, $child));
    }
    
    omni_trace("setting up pipes for stdin / stdout / stderr");
    
    $stdin_pipe = new Pipe();
    $stdout_pipe = new Pipe();
    $stdout_pipe->attachStdoutEndpoint(Endpoint::FORMAT_USER_FRIENDLY);
    $stderr_pipe = new Pipe();
    $stderr_pipe->attachStderrEndpoint(Endpoint::FORMAT_USER_FRIENDLY);
    
    omni_trace("configuring job background / foreground before execution");
    
    if ($data['data'] === 'foreground') {
      $job->setForeground(true);
      
      // FIXME As soon as attachStdinEndpoint is run, the standard input controller
      // starts.  If there's an exception later on, we aren't killing the standard
      // input controller (or any of the other controllers for that matter).  We
      // probably need to give jobs an exception property, and then write any
      // exception that occurs to that property, before finally sending SIGKILL to
      // any processes in the job (in addition, we should always add these controllers
      // as processes to the job, but refer to the TODO in the Job code around standard
      // input handling).
      $stdin_pipe->attachStdinEndpoint(Endpoint::FORMAT_BYTE_STREAM);
      
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
