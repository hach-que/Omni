<?php

final class Shell extends Phobject {

  const STDIN_FILENO = 0;
  const STDOUT_FILENO = 1;
  const STDERR_FILENO = 2;

  private $pgid = null;
  private $tmodes;
  private $terminal;
  private $isInteractive;
  private $jobs = array();
  private $variables = array();
  
  public function findJob($pgid) {
    return idx($this->jobs, $pgid, null);
  }
  
  public function run() {
    // Check if we are running interactively.
    $this->terminal = self::STDIN_FILENO;
    $this->isInteractive = posix_isatty($this->terminal);
    
    if ($this->isInteractive) {
      // Loop until we are in the foreground.
      while (omni_tcgetpgrp($this->terminal) != ($this->pgid = posix_getpgrp())) {
        posix_kill(-$this->pgid, SIGTTIN);
      }
      
      // Ignore interactive and job-control signals.
      pcntl_signal(SIGINT, SIG_IGN);
      pcntl_signal(SIGQUIT, SIG_IGN);
      pcntl_signal(SIGTSTP, SIG_IGN);
      pcntl_signal(SIGTTIN, SIG_IGN);
      pcntl_signal(SIGTTOU, SIG_IGN);
      pcntl_signal(SIGCHLD, SIG_IGN);
      
      // Put ourselves in our own process group.
      $this->pgid = posix_getpid();
      if (posix_setpgid($this->pgid, $this->pgid) < 0) {
        self::writeToStderr("Couldn't put the shell in it's own process group");
        exit(1);
      }
      
      omni_tcsetpgrp($this->terminal, $this->pgid);
      
      $this->tmodes = omni_tcgetattr($this->terminal);
    }
  }
  
  public function launchProcess(
    Process $process, 
    $inFD = self::STDIN_FILENO,
    $outFD = self::STDOUT_FILENO,
    $errFD = self::STDERR_FILENO,
    $foreground = true) {
    
    if ($this->isInteractive) {
      // Put the process into the process group and give
      // the process group the terminal, if appropriate.
      // This has to be done both by the shell and in the
      // individual child processes because of potential
      // race conditions.
      $pid = posix_getpid();
      if ($this->pgid === null) {
        $this->pgid = $pid;
      }
      posix_setpgid($pid, $this->pgid);
      if ($foreground) {
        omni_tcsetpgrp($this->terminal, $this->pgid);
      }
      
      // Restore signal handling back to the default.
      pcntl_signal(SIGINT, SIG_DFL);
      pcntl_signal(SIGQUIT, SIG_DFL);
      pcntl_signal(SIGTSTP, SIG_DFL);
      pcntl_signal(SIGTTIN, SIG_DFL);
      pcntl_signal(SIGTTOU, SIG_DFL);
      pcntl_signal(SIGCHLD, SIG_DFL);
    }
    
    if ($inFD !== self::STDIN_FILENO) {
      omni_dup2($inFD, self::STDIN_FILENO);
      omni_close($inFD);
    }
    if ($outFD !== self::STDOUT_FILENO) {
      omni_dup2($outFD, self::STDOUT_FILENO);
      omni_close($outFD);
    }
    if ($errFD !== self::STDERR_FILENO) {
      omni_dup2($errFD, self::STDERR_FILENO);
      omni_close($errFD);
    }
    
    $argv = $process->argv;
    $path = array_shift($argv);
    
    if (pcntl_exec($path, $argv) === false) {
      self::writeToStderr("error: exec: ".pcntl_strerror()."\n");
      exit(1);
    }
  }
  
  public function launchJob(Job $job, $foreground = true) {
    $process = null;
    $pid = null;
    $pipe = null;
    
    $myfile = array();
    $infile = $job->stdin;
    $outfile = null;
    
    $processes = $job->processes;
    $processes_count = count($processes);
    for ($i = 0; $i < $processes_count; $i++) {
      $process = $processes[$i];
    
      if ($i !== $processes_count - 1) {
        // If this is not the last process in the list,
        // then we need a pipe to connect it to.
        $pipe = omni_pipe();
      
        if ($pipe === false) {
          echo "error: pipe\n";
          exit(1);
        }
        
        $outfile = $pipe['write'];
      } else {
        // This is the last process, so connect it to
        // the job standard output.
        $outfile = $job->stdout;
      }
      
      // Fork the child process.
      $pid = pcntl_fork();
      if ($pid === 0) {
        // This is the child process.
        $this->launchProcess(
          $process,
          $infile,
          $outfile,
          $job->stderr,
          $foreground);
      } else if ($pid < 0) {
        // The fork failed.
        self::writeToStderr("error: fork\n");
        exit(1);
      } else {
        // This is the parent process.
        $process->pid = $pid;
        if ($this->isInteractive) {
          if ($job->pgid === null) {
            $job->pgid = $pid;
          }
          posix_setpgid($pid, $job->pgid);
        }
      }
      
      // Clean up after pipes.
      if ($infile !== $job->stdin) {
        omni_close($infile);
      }
      if ($outfile !== $job->stdout) {
        omni_close($outfile);
      }
      if ($pipe !== null) {
        $infile = $pipe['read'];
      }
    }

    //$this->formatJobInfo($job, "launched");
    
    if (!$this->isInteractive) {
      $this->waitForJob($job);
    } else if ($foreground) {
      $this->putJobInForeground($job, false);
    } else {
      $this->putJobInBackground($job, false);
    }
  }
  
  public function putJobInForeground(Job $job, $continue = false) {
    // Put the job into the foreground.
    omni_tcsetpgrp($this->terminal, $job->pgid);
    
    // Send the job a continue signal, if necessary.
    if ($continue) {
      omni_tcsetattr_tcsadrain($this->terminal, $job->tmodes);
      if (!posix_kill(-$job->pgid, SIGCONT)) {
        self::writeToStderr("error: kill SIGCONT\n");
      }
    }
    
    $this->waitForJob($job);
    
    // Put the shell back into the foreground.
    omni_tcsetpgrp($this->terminal, $job->pgid);
    
    // Restore the shell's terminal modes.
    $tmodes = omni_tcgetattr($this->terminal);
    omni_tcsetattr_tcsadrain($this->terminal, $tmodes);
  }
  
  public function putJobInBackground(Job $job, $continue = false) {
    // Send the job a continue signal, if necessary.
    if ($continue) {
      if (!posix_kill(-$job->pgid, SIGCONT)) {
        self::writeToStderr("error: kill SIGCONT\n");
      }
    }
  }
  
  public function markProcessStatus($pid, $status) {
    if ($pid > 0) {
      // Update the record for the process.
      foreach ($this->jobs as $job) {
        foreach ($job->processes as $process) {
          if ($process->pid === $pid) {
            $process->status = $status;
            if (pcntl_wifstopped($status)) {
              $process->stopped = true;
            } else {
              $process->completed = true;
              if (pcntl_wifsignaled($status)) {
                // TODO stderr
                self::writeToStderr(
                  $pid.": Terminated by signal ".
                  pcntl_wtermsig($process->status).".\n");
              }
            }
            return 0;
          }
        }
      }
      
      // TODO stderr
      //self::writeToStderr("No child process ".$pid.".\n");
      return -1;
    } else if ($pid === 0 || pcntl_get_last_error() == 10 /* ECHILD */) {
      // No processes ready to report.
      return -1;
    } else {
      // Other weird errors.
      self::writeToStderr("error: waitpid: ".pcntl_strerror(pcntl_get_last_error())."\n");
      return -1;
    }
  }
  
  public function updateStatus() {
    $status = null;
    $pid = null;
    
    do {
      $pid = pcntl_waitpid(-1, $status, WUNTRACED | WNOHANG);
    } while (!$this->markProcessStatus($pid, $status));
  }
  
  public function waitForJob($job) {
    $status = null;
    $pid = null;
    
    do {
      $pid = pcntl_waitpid(-1, $status, WUNTRACED);
    } while (
      !$this->markProcessStatus($pid, $status) &&
      !$job->isStopped() &&
      !$job->isCompleted());
  }
  
  public function formatJobInfo(Job $job, $status) {
    self::writeToStderr($job->pgid." (".$status."): ".$job->command."\n");
  }
  
  public static function writeToStderr($message) {
    static $stderr;
    $stderr = fopen('php://stderr', 'w+');
    fwrite($stderr, $message);
  }
  
  public function doJobNotification() {
    $this->updateStatus();
    
    foreach ($this->jobs as $key => $job) {
      // If all processes have completed, tell the user the
      // job has completed and delete it from the list of
      // active jobs.
      if ($job->isCompleted()) {
        $this->formatJobInfo($job, "completed");
        unset($this->jobs[$key]);
      }
      
      // Notify the user about stopped jobs.
      else if ($job->isStopped() && !$job->notified) {
        $this->formatJobInfo($job, "stopped");
        $job->notified = true;
      }
    }
  }
  
  public function markJobAsRunning(Job $job) {
    foreach ($job->processes as $process) {
      $process->stopped = false;
    }
    
    $job->notified = false;
  }
  
  public function continueJob(Job $job, $foreground = true) {
    $this->markJobAsRunning($job);
    if ($foreground) {
      $this->putJobInForeground($job, true);
    } else {
      $this->putJobInBackground($job, true);
    }
  }
  
  public function execute($input) {
    $results = omnilang_parse($input);
    id(new RootVisitor())->visit($this, $results);
  }
  
  public function executeFromArray($argv) {
    // TODO
  }
  
  public function setVariable($key, $value) {
    $this->variables[$key] = $value;
  }
  
  public function getVariable($key) {
    return idx($this->variables, $key, null);
  }
}