#!/usr/bin/env php
<?php

$pipe = fd_pipe();

echo "parent: started\n";
$pid = pcntl_fork();
if ($pid === 0) {
  echo "child1: started\n";
  fd_close($pipe['write']);
  
  $pipe2 = fd_pipe();
  
  $pid = pcntl_fork();
  if ($pid === 0) {
    echo "child2: started\n";
    fd_close($pipe2['write']);
    fd_close($pipe['read']);
    
    // CHILD PROCESS 2
    // reads from $pipe2
    echo "child2: sleeping 2 seconds...\n";
    sleep(2);
    echo "child2: closing pipe2 read...\n";
    fd_close($pipe2['read']);
    echo "child2: sleeping 10 seconds...\n";
    sleep(10);
    
    echo "child2: exiting...\n";
    exit(0);
  } else if ($pid > 0) {
    fd_close($pipe2['read']);
    
    // CHILD PROCESS 1
    // reads from $pipe and writes to $pipe2
    $result = fd_select(array($pipe['read']), array(), array($pipe2['write']));
    echo "child1: RETURNED FROM fd_select!";
    
    echo "child1: exiting...\n";
    exit(0);
  } else {
    echo "error while forking\n";
    exit(1);
  }
} else if ($pid > 0) {
  fd_close($pipe['read']);
  
  // PARENT PROCESS
  // writes to $pipe
  
  // We wait here to simulate waiting for input from stdin.
  echo "parent: sleeping for 60 seconds\n";
  sleep(60);
  
  echo "parent: exiting...\n";
  exit(0);
} else {
  echo "parent: error while forking\n";
  exit(1);
} 