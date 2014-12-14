<?php

final class JobBuiltin extends Builtin {

  public function getName() {
    return 'job';
  }

  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    Pipe $stdin, 
    Pipe $stdout, 
    Pipe $stderr) {
    
    $stdout_endpoint = $stdout->createInboundEndpoint();
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull(array(
      array(
        'name' => 'job',
        'short' => 'j',
        'help' => 'If specified, operates on a specific job.'
      ),
    ));
    
    if ($parser->getArg('job')) {
      // Operate on a specific job.
      $job = $shell->findJob($parser->getArg('job'));
      
      foreach ($job->getProcesses() as $process) {
        $stdout_endpoint->write(array(
          'pid' => $process->getProcessID(),
          'type' => $process->getProcessType(),
          'status' => $process->getProcessStatus(),
          'stopped?' => $process->isStopped(),
          'completed?' => $process->isCompleted(),
        ));
      }
    } else {
      // Operates on all of the jobs.
      foreach ($shell->getJobs() as $job) {
        $stdout_endpoint->write(array(
          'pgid' => $job->getProcessGroupID(),
          'stages' => count($job->getStages()),
          'processes' => count($job->getProcesses()),
          'foreground?' => $job->isForeground(),
          'stopped?' => $job->isStopped(),
          'completed?' => $job->isCompleted(),
        ));
      }
      
    }
  }

}