<?php

final class InteractiveTTYManual extends Phobject {

  private $shell;
  private $parser;

  public function run() {
    $this->shell = new Shell();
    $this->parser = new Parser();
    
    $this->shell->run();
    
    exec("stty -icanon");
    
    stream_set_blocking(STDIN, false);
    $line = '';
    $time = microtime(true);
    $prompt = "#> ";
    echo "#> ";
    
    while (true) {
      if (microtime(true) - $time > 5) {
        echo "\nTick...\n$prompt$line";
        $time = microtime(true);
      }
      
      $c = fgetc(STDIN);
      if ($c !== false) {
        if ($c != "\n") {
          $line .= $c;
        } else {
          if ($line === "quit") {
            break;
          }
        
          $process = new Process();
          $process->argv = explode(' ', $line); // TODO Better parsing
            
          $job = new Job();
          $job->processes = array(
            $process,
          );
          $job->stdin = Shell::STDIN_FILENO;
          $job->stdout = Shell::STDOUT_FILENO;
          $job->stderr = Shell::STDERR_FILENO;
          
          $this->shell->launchJob($job);
          
          echo $prompt;
          $line = '';
        }
      }
    }
  }

}