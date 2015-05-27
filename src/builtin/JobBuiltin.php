<?php

final class JobBuiltin extends Builtin {

  public function getName() {
    return 'job';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    $stdout_endpoint = $stdout->createInboundEndpoint(null, "job stdout");
    $stderr_endpoint = $stderr->createInboundEndpoint(null, "job stderr");
    
    return array(
      'stdout' => $stdout_endpoint,
      'stderr' => $stderr_endpoint,
      'close_read_on_fork' => array(),
      'close_write_on_fork' => array(
        $stdout_endpoint,
        $stderr_endpoint,
      ),
    );
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(
      array(
        'name' => 'job',
        'param' => 'job',
        'short' => 'j',
        'help' => 'If specified, operates on a specific job.'
      ),
    );
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    $stderr = idx($prepare_data, 'stderr');
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull($this->getArguments($shell, $job, $prepare_data));
    
    if ($parser->getArg('job')) {
      // Operate on a specific job.
      $job = $shell->findJob($parser->getArg('job'));
      if ($job === null) {
        $stderr->write(new Exception('No such job!'));
        $stdout->closeWrite();
        $stderr->closeWrite();
        return;
      }
      
      foreach ($job->getProcesses() as $process) {
        $stdout->write(array(
          'pid' => $process->getProcessID(),
          'type' => $process->getProcessType(),
          'description' => $process->getProcessDescription(),
          'status' => $process->getProcessStatus(),
          'stopped?' => $process->isStopped(),
          'completed?' => $process->isCompleted(),
          'exitCode' => $process->getExitCode(),
        ));
      }
    } else {
      // Operates on all of the jobs.
      foreach ($shell->getJobs() as $job) {
        $stdout->write(array(
          'pgid' => $job->getProcessGroupIDOrNull(),
          'processes' => count($job->getProcesses()),
          'foreground?' => $job->isForeground(),
          'stopped?' => $job->isStopped(),
          'completed?' => $job->isCompleted(),
          'exitCode' => $job->getExitCode(),
        ));
      }
    }
    
    $stdout->closeWrite();
    $stderr->closeWrite();
  }

}