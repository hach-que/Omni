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
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(null, "job stdout"),
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
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull($this->getArguments($shell, $job, $prepare_data));
    
    if ($parser->getArg('job')) {
      // Operate on a specific job.
      $job = $shell->findJob($parser->getArg('job'));
      
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
          'stages' => count($job->getStages()),
          'processes' => count($job->getProcesses()),
          'foreground?' => $job->isForeground(),
          'stopped?' => $job->isStopped(),
          'completed?' => $job->isCompleted(),
          'exitCode' => $job->getExitCode(),
        ));
      }
    }
    
    $stdout->closeWrite();
  }

}