#!/usr/bin/env php
<?php

echo "parent: creating control pipe...\n";
$control_pipe = fd_control_pipe();
$read_control_fd = $control_pipe['read'];
$write_control_fd = $control_pipe['write'];

$pid = pcntl_fork();
if ($pid === 0) {
  fd_close($read_control_fd);
  
  $pipe_to_send = fd_pipe();
  $write_fd_to_send = $pipe_to_send['write'];
  $read_fd_to_keep = $pipe_to_send['read'];
  
  echo "child: Writing FD $write_fd_to_send over control pipe FD $write_control_fd...\n";
  fd_control_writefd($write_control_fd, $write_fd_to_send);
  
  usleep(5000);
  
  echo "child: Reading data from $read_fd_to_keep...\n";
  $result = fd_read($read_fd_to_keep, 5);
  echo "child: ".$result."\n";
  
  fd_close($write_control_fd);
  echo "child: Closing write FD that was sent...\n";
  fd_close($write_fd_to_send);
  exit(0);
} else if ($pid > 0) {
  echo "parent: Closing write control FD...\n";
  fd_close($write_control_fd);
  
  echo "parent: Reading FD from control pipe...\n";
  $write_fd_to_use = fd_control_readfd($read_control_fd);
  
  echo "parent: Writing data into FD to use...\n";
  fd_write($write_fd_to_use, "hello");
  
  usleep(5000);
  
  fd_close($read_control_fd);
  fd_close($write_fd_to_use);
} else {
  echo "failed to fork";
  exit(1);
} 