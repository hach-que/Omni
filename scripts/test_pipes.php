#!/usr/bin/env php
<?php

$pipe = omni_pipe();

$pid = pcntl_fork();
if ($pid === 0) {
  // This is the child process.
  echo "child started\n";
  omni_dup2($pipe['read'], 0);
  omni_close($pipe['read']);
  $file = fopen("php://stdin", 'r');
  echo "input received: ".fread($file, 5);
  echo "\ndone\n";
  fclose($file);
} else {
  $file = fopen("php://fd/".$pipe['write'], 'w');
  fwrite($file, "hello");
  fclose($file);
}