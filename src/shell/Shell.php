<?php

final class Shell extends Phobject implements HasTerminalModesInterface {

  const STDIN_FILENO = 0;
  const STDOUT_FILENO = 1;
  const STDERR_FILENO = 2;

  private $shellProcessGroupID = null;
  private $tmodes;
  private $terminal;
  private $isInteractive;
  private $jobs = array();
  private $variables = array();
  private $builtins;
  
  public function __construct() {
    $this->builtins = id(new PhutilSymbolLoader())
      ->setAncestorClass('Builtin')
      ->loadObjects();
    $this->builtins = mpull($this->builtins, null, 'getName');
  }
  
  
/* -(  Shell Initialization  )----------------------------------------------- */
  
  
  public function initialize() {
    // Check if we are running interactively.
    $this->terminal = self::STDIN_FILENO;
    $this->isInteractive = posix_isatty($this->terminal);
    
    if ($this->isInteractive) {
      // Loop until we are in the foreground.
      while (tc_tcgetpgrp($this->terminal) != ($this->shellProcessGroupID = posix_getpgrp())) {
        posix_kill(-$this->shellProcessGroupID, SIGTTIN);
      }
      
      // Ignore interactive and job-control signals.
      pcntl_signal(SIGINT, SIG_IGN);
      pcntl_signal(SIGQUIT, SIG_IGN);
      pcntl_signal(SIGTSTP, SIG_IGN);
      pcntl_signal(SIGTTIN, SIG_IGN);
      pcntl_signal(SIGTTOU, SIG_IGN);
      pcntl_signal(SIGCHLD, SIG_IGN);
      
      // Put ourselves in our own process group.
      $this->shellProcessGroupID = posix_getpid();
      if (posix_setpgid($this->shellProcessGroupID, $this->shellProcessGroupID) < 0) {
        omni_trace("Couldn't put the shell in it's own process group");
        omni_exit(1);
      }
      
      // Grab control of the terminal.
      $this->takeControlOfTerminal();
      
      // Save the terminal defaults for the shell.
      $this->setTerminalModes($this->captureCurrentTerminalModes());
    }
  }
  
  
/* -(  Launching Processes  )------------------------------------------------ */
  
  
  public function launchProcess(
    array $argv,
    Job $job,
    $inFD = self::STDIN_FILENO,
    $outFD = self::STDOUT_FILENO,
    $errFD = self::STDERR_FILENO) {
    
    if ($this->isInteractive) {
      $this->putProcessInProcessGroupIfInteractiveFromChild($job);
      
      $this->giveControlOfTerminalToJobIfForeground($job);
      
      // Restore signal handling back to the default.
      pcntl_signal(SIGINT, SIG_DFL);
      pcntl_signal(SIGQUIT, SIG_DFL);
      pcntl_signal(SIGTSTP, SIG_DFL);
      pcntl_signal(SIGTTIN, SIG_DFL);
      pcntl_signal(SIGTTOU, SIG_DFL);
      pcntl_signal(SIGCHLD, SIG_DFL);
    }
    
    if ($inFD !== self::STDIN_FILENO) {
      fd_dup2($inFD, self::STDIN_FILENO);
      fd_close($inFD);
    }
    if ($outFD !== self::STDOUT_FILENO) {
      fd_dup2($outFD, self::STDOUT_FILENO);
      fd_close($outFD);
    }
    if ($errFD !== self::STDERR_FILENO) {
      fd_dup2($errFD, self::STDERR_FILENO);
      fd_close($errFD);
    }
    
    $path = array_shift($argv);
    
    if (@pcntl_exec($path, $argv) === false) {
      omni_trace("error: $path: ".pcntl_strerror(pcntl_get_last_error())."\n");
      omni_exit(1);
    }
  }
  
  
/* -(  Job Scheduling and Process Management )------------------------------- */
  
  
  public function getJobs() {
    return $this->jobs;
  }
  
  public function findJob($pgid) {
    return idx($this->jobs, $pgid, null);
  }
  
  public function scheduleJob(Job $job) {
    $this->jobs[$job->getProcessGroupIDOrAssert()] = $job;
  
    if (!$this->isInteractive) {
      $this->waitForJob($job);
    } else if ($job->isForeground()) {
      $this->putJobInForeground($job, false);
    } else {
      $this->putJobInBackground($job, false);
    }
  }
  
  public function putJobInForeground(Job $job, $continue = false) {
    $job->setForeground(true);
  
    // Put the job into the foreground.
    $this->giveControlOfTerminalToJobIfForeground($job);
    
    // Send the job a continue signal, if necessary.
    if ($continue) {
      $this->applyTerminalModes($job);
      
      if (!posix_kill(-$job->getProcessGroupIDOrAssert(), SIGCONT)) {
        omni_trace("error: kill SIGCONT\n");
      }
    }
    
    omni_trace("waiting for foreground job to complete");
    
    $this->waitForJob($job);
    
    omni_trace("foreground job has finished being waited on");
    if ($job->isCompleted()) {
      omni_trace("foreground job has completed");
    } elseif ($job->isStopped()) {
      omni_trace("foreground job has stopped");
    } else {
      omni_trace("WARNING: foreground job is neither completed nor stopped!");
    }
    
    // Put the shell back into the foreground.
    $this->takeControlOfTerminal();
    
    // Restore the shell's terminal modes.
    $job->setTerminalModes($this->captureCurrentTerminalModes());
    $this->applyTerminalModes($this);
  }
  
  public function putJobInBackground(Job $job, $continue = false) {
    // Send the job a continue signal, if necessary.
    if ($continue) {
      if (!posix_kill(-$job->getProcessGroupIDOrAssert(), SIGCONT)) {
        omni_trace("error: kill SIGCONT\n");
      }
    }
  }
  
  private function markProcessStatus($pid, $status) {
    if ($pid > 0) {
      // Update the record for the process.
      foreach ($this->jobs as $job) {
        foreach ($job->getProcesses() as $process) {
          if ($process->getProcessID() === $pid) {
            $process->setProcessStatus($status);
            if (pcntl_wifstopped($status)) {
              $process->setStopped(true);
            } else {
              $process->setCompleted(true);
              if (pcntl_wifsignaled($status)) {
                omni_trace(
                  $pid.": Terminated by signal ".
                  pcntl_wtermsig($process->getProcessStatus()).".\n");
              }
            }
            
            // Perform any clean up operations for the job when
            // it's completed.
            if ($job->isCompleted()) {
              $job->finalize();
            }
            
            return false;
          }
        }
      }
      
      omni_trace("No child process ".$pid.".\n");
      
      // NOTE GNU C Shell example shows this as returning true, but if we
      // return true here we'll break out of waitForJob early if we get
      // notifications about processes that we're no longer tracking.  Since
      // we don't care about processes we're no longer tracking, and we don't
      // want them to cause waitForJob to exit early, we return false here
      // instead.
      return false;
    } else if ($pid === 0 || pcntl_get_last_error() == 10 /* ECHILD */) {
      // No processes ready to report.
      return true;
    } else {
      // Other weird errors.
      omni_trace("error: waitpid: ".pcntl_strerror(pcntl_get_last_error())."\n");
      return true;
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
      omni_trace("got signal for $pid: $status\n");
      
      foreach ($job->getProcesses() as $process) {
        omni_trace(print_r(array(
          'pid' => $process->getProcessID(),
          'type' => $process->getProcessType(),
          'status' => $process->getProcessStatus(),
          'stopped?' => $process->isStopped(),
          'completed?' => $process->isCompleted(),
        ), true));
      }
    } while (
      !$this->markProcessStatus($pid, $status) &&
      !$job->isStopped() &&
      !$job->isCompleted());
  }
  
  public function formatJobInfo(Job $job, $status) {
    omni_trace($job->getProcessGroupIDOrAssert()." (".$status."): ".$job->getCommand()."\n");
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
        $job->setUserBeenNotifiedOfNewStatus(true);
      }
    }
  }
  
  public function markJobAsRunning(Job $job) {
    foreach ($job->getProcesses() as $process) {
      $process->setStopped(false);
    }
    
    $job->setUserBeenNotifiedOfNewStatus(false);
  }
  
  public function continueJob(Job $job) {
    $this->markJobAsRunning($job);
    if ($job->isForeground()) {
      $this->putJobInForeground($job, true);
    } else {
      $this->putJobInBackground($job, true);
    }
  }
  
  
/* -(  Builtins  )----------------------------------------------------------- */
  
  
  public function isKnownBuiltin($name) {
    return idx($this->builtins, $name, null) !== null;
  }
  
  public function lookupBuiltin($name) {
    return idx($this->builtins, $name, null);
  }
  
  
/* -(  Execute Commands  )--------------------------------------------------- */
  
  
  public function execute($input) {
    omni_trace("start parse");
    
    $results = omnilang_parse($input);
    
    if ($results === false) {
      echo omnilang_get_error()."\n";
      omni_trace("execute failed due to parse error: ".omnilang_get_error());
    } else {
      omni_trace("visit nodes with result");
      
      id(new RootVisitor())->visit($this, $results);
      
      omni_trace("execute complete");
    }
  }
  
  public function executeFromArray($argv) {
    $this->execute(implode(' ', $argv));
  }
  
  
/* -(  Set and Get Shell Terminal Modes  )----------------------------------- */
  
  
  public function setTerminalModes($terminal_modes) {
    $this->tmodes = $terminal_modes;
    return $this;
  }
  
  public function getTerminalModes() {
    return $this->tmodes;
  }
  
  
/* -(  Apply and Restore Terminal Modes  )----------------------------------- */
  
  
  private function applyTerminalModes(HasTerminalModesInterface $modes) {
    tc_tcsetattr($this->terminal, tc_tcsadrain(), $modes->getTerminalModes());
  }
  
  private function captureCurrentTerminalModes() {
    return tc_tcgetattr($this->terminal);
  }
  
  
/* -(  Terminal Control  )--------------------------------------------------- */
  
  
  private function giveControlOfTerminalToJobIfForeground(Job $job) {
    if ($job->isForeground()) {
      // Give control of the terminal to the child process.
      tc_tcsetpgrp($this->terminal, $job->getProcessGroupIDOrAssert());
    }
  }
  
  private function takeControlOfTerminal() {
    tc_tcsetpgrp($this->terminal, $this->shellProcessGroupID);
  }
  
  
/* -(  Process Groups  )----------------------------------------------------- */
  
  
  public function putProcessInProcessGroupIfInteractive(Job $job, $pid) {
    if ($this->isInteractive) {
      posix_setpgid($pid, $job->getProcessGroupID($pid));
    }
  }
  
  private function putProcessInProcessGroupIfInteractiveFromChild(Job $job) {
    // Put the process into the process group and give
    // the process group the terminal, if appropriate.
    // This has to be done both by the shell and in the
    // individual child processes because of potential
    // race conditions.
    $pid = posix_getpid();
    $pgid = $job->getProcessGroupID($pid);
    posix_setpgid($pid, $pgid);
  }
  
  
/* -(  Shell Variables  )---------------------------------------------------- */
  
  
  public function setVariable($key, $value) {
    $this->variables[$key] = $value;
  }
  
  public function getVariable($key) {
    return idx($this->variables, $key, null);
  }
}