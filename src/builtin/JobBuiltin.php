<?php

final class JobBuiltin extends Builtin {

  public function getName() {
    return 'job';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    Pipe $stdin,
    Pipe $stdout,
    Pipe $stderr) {
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(),
    );
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull(array(
      array(
        'name' => 'job',
        'param' => 'job',
        'short' => 'j',
        'help' => 'If specified, operates on a specific job.'
      ),
    ));
    
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
        ));
      }
    }
    
    $stdout->closeWrite();
  }

}