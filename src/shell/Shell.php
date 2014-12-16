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
  private $isExiting;
  
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
      omni_trace("ensuring foreground control");
      
      // Loop until we are in the foreground.
      while (tc_tcgetpgrp($this->terminal) != ($this->shellProcessGroupID = posix_getpgrp())) {
        omni_trace("sending SIGTTIN to ".$this->shellProcessGroupID." because it's not ".tc_tcgetpgrp($this->terminal));
      
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
  
  
/* -(  Background SIGCHLD  )------------------------------------------------- */

  
  public function receivedChildSignal() {
    omni_trace("got SIGCHLD");
    $this->waitForAnyJob();
  }
  
  
/* -(  Shell Shutdown  )----------------------------------------------------- */
  
  
  public function requestExit() {
    $this->isExiting = true;
  }
  
  public function wantsToExit() {
    return $this->isExiting;
  }
  
  public function finalize() {
    omni_trace("waiting for remaining jobs");
    
    $stderr_endpoint = $this->createInternalStderrEndpoint();
    
    $waiting = true;
    $last_count = -1;
    while ($waiting) {
      if ($last_count >= 0) {
        usleep(5000);
      }

      omni_trace("checking jobs");
      
      $waiting = false;
      $wait_count = 0;
      foreach ($this->getJobs() as $job) {
        if (!$job->isCompleted()) {
          if (!$job->isStopped()) {
            omni_trace("job ".$job->getProcessGroupIDOrNull()." not stopped and not completed");
            $waiting = true;
            $wait_count++;
          }
        }
      }
      
      if ($waiting && $wait_count !== $last_count) {
        $stderr_endpoint->write('Waiting for '.$wait_count.' background jobs to complete...'."\n");
        $last_count = $wait_count;
      }
      
      $this->doJobNotification();
    }
    
    $stderr_endpoint->closeWrite();
    
    omni_trace("restoring terminal modes");
    
    if ($this->isInteractive) {
      $this->applyTerminalModes($this);
    }
    
    omni_trace("remaining jobs completed; ready to quit");
  }
  
  
/* -(  General Output  )----------------------------------------------------- */


  public function createInternalStderrEndpoint() {
    return id(new Endpoint(array('read' => null, 'write' => Shell::STDERR_FILENO)))
      ->setName("shell stderr")
      ->setWriteFormat(Endpoint::FORMAT_USER_FRIENDLY)
      ->setClosable(false);
  }
  
  
/* -(  Launching Processes  )------------------------------------------------ */
  
  
  public function prepareForkedProcess(Job $job, $dont_reset_sigttin = false) {
    if ($this->isInteractive) {
      $this->putProcessInProcessGroupIfInteractiveFromChild($job);
      
      $this->giveControlOfTerminalToJobIfForeground($job);
      
      // Restore signal handling back to the default.
      pcntl_signal(SIGINT, SIG_DFL);
      pcntl_signal(SIGQUIT, SIG_DFL);
      pcntl_signal(SIGTSTP, SIG_DFL);
      if (!$dont_reset_sigttin) {
        pcntl_signal(SIGTTIN, SIG_DFL);
      }
      pcntl_signal(SIGTTOU, SIG_DFL);
      pcntl_signal(SIGCHLD, SIG_DFL);
    }
  }
  
  public function launchProcess(
    array $argv,
    Job $job,
    $inFD = self::STDIN_FILENO,
    $outFD = self::STDOUT_FILENO,
    $errFD = self::STDERR_FILENO) {
    
    $this->prepareForkedProcess($job);
    
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
    $job->setForeground(false);
  
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
              omni_trace(
                $pid." was stopped.\n");
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
      omni_trace("no processes ready to report");
      return true;
    } else {
      // Other weird errors.
      omni_trace("error: waitpid: ".pcntl_strerror(pcntl_get_last_error()));
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
  
  public function waitForAnyJob() {
    $status = null;
    $pid = null;
    
    do {
      $pid = pcntl_waitpid(-1, $status, WUNTRACED);
    } while (!$this->markProcessStatus($pid, $status));
  }
  
  public function waitForJob($job) {
    $status = null;
    $pid = null;
    
    do {
      omni_trace("waiting on any child pid");
      $pid = pcntl_waitpid(-1, $status, WUNTRACED);
      omni_trace("got signal for $pid: $status");
    } while (
      !$this->markProcessStatus($pid, $status) &&
      !$job->isStopped() &&
      !$job->isCompleted());
  }
  
  public function formatJobInfo(Job $job, $status) {
    $endpoint = $this->createInternalStderrEndpoint();
    $endpoint->write($job->getProcessGroupIDOrAssert()." (".$status."): ".$job->getCommand()."\n");
    $endpoint->closeWrite();
  }
  
  public function doJobNotification() {
    $this->updateStatus();
    
    omni_trace("updated all processes from waitpid");
    
    foreach ($this->jobs as $key => $job) {
      // If all processes have completed, tell the user the
      // job has completed and delete it from the list of
      // active jobs.
      if ($job->isCompleted()) {
        if (!$job->isForeground()) {
          // Only display "completed" for background jobs.
          $this->formatJobInfo($job, "completed");
        }
        unset($this->jobs[$key]);
      }
      
      // Notify the user about stopped jobs.
      else if ($job->isStopped() && !$job->hasUserBeenNotifiedOfNewStatus()) {
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
      
      omni_trace("before doJobNotification");
      
      $this->doJobNotification();
      
      omni_trace("after doJobNotification");
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
      omni_trace("giving control of terminal to ".$job->getProcessGroupIDOrAssert());
      tc_tcsetpgrp($this->terminal, $job->getProcessGroupIDOrAssert());
    }
  }
  
  public function takeControlOfTerminal() {
    omni_trace("taking control of terminal back to ".$this->shellProcessGroupID);
    if (!tc_tcsetpgrp($this->terminal, $this->shellProcessGroupID)) {
      omni_trace("FAILED TO TAKE CONTROL OF TERMINAL!!!!");
      omni_trace("ERROR FROM TCSETPGRP WAS: ".tc_get_error());
      throw new Exception('Unable to regain control of terminal');
    }
    
    omni_trace("shell process group ID as per posix_getpgid: ".posix_getpgid(posix_getpid()));
    omni_trace("shell process group ID as per posix_getpgrp: ".posix_getpgrp());
    omni_trace("shell process group ID as per private variable: ".$this->shellProcessGroupID);
    omni_trace("controlling process group ID as per tc_tcgetpgrp:".tc_tcgetpgrp($this->terminal));
  }
  
  public function verifyReadingFromStandardInputWillWork() {
    if (tc_tcgetpgrp($this->terminal) !== $this->shellProcessGroupID) {
      throw new Exception('Shell process group is not controlling terminal');
    }
    
    if (posix_getpgid(posix_getppid()) === false) {
      throw new Exception('Shell\'s parent process is no longer running; won\'t be able to recover stdin');
    }
  }
  
  
/* -(  Process Groups  )----------------------------------------------------- */
  
  
  public function putProcessInProcessGroupIfInteractive(Job $job, $pid) {
    if ($this->isInteractive) {
      if (!is_integer($pid)) {
        throw new Exception('Non-integer PID passed to putProcessInProcessGroupIfInteractive');
      }
      if ($pid === $this->shellProcessGroupID) {
        throw new Exception('Attempting to place shell into job process group!');
      }
      $pgid = $job->getProcessGroupID($pid);
      omni_trace("moving $pid into process group ".$pgid);
      posix_setpgid($pid, $pgid);
    }
  }
  
  private function putProcessInProcessGroupIfInteractiveFromChild(Job $job) {
    // Put the process into the process group and give
    // the process group the terminal, if appropriate.
    // This has to be done both by the shell and in the
    // individual child processes because of potential
    // race conditions.
    $pid = posix_getpid();
    if (!is_integer($pid)) {
      throw new Exception('Non-integer PID returned from posix_getpid in putProcessInProcessGroupIfInteractiveFromChild');
    }
    if ($pid === $this->shellProcessGroupID) {
      throw new Exception('Called putProcessInProcessGroupIfInteractiveFromChild while still in shell!');
    }
    $pgid = $job->getProcessGroupID($pid);
    omni_trace("moving $pid into process group $pgid");
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