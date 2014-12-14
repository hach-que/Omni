#!/usr/bin/env php
<?php

$pipe = fd_pipe();
$read_fd = $pipe['read'];
$write_fd = $pipe['write'];

$pid = pcntl_fork();
if ($pid === 0) {
  fd_close($read_fd);
  
  fd_write($write_fd, "abc");
  
  usleep(5000);
  
  fd_write($write_fd, "def");
  
  usleep(5000);
  
  fd_close($write_fd);
  exit(0);
} else if ($pid > 0) {
  fd_close($write_fd);
  
  $read_array = array($read_fd);
  $write_array = array();
  $except_array = array();
  $ready = fd_select($read_array, $write_array, $except_array);
  
  $data = fd_read($read_fd, 3);
  if ("abc" !== $data) {
    echo "$data is not abc";
    exit(1);
  }
  
  $read_array = array($read_fd);
  $write_array = array();
  $except_array = array();
  $ready = fd_select($read_array, $write_array, $except_array);
  
  $data = fd_read($read_fd, 3);
  if ("def" !== $data) {
    echo "$data is not def";
    exit(1);
  }
  
  $read_array = array($read_fd);
  $write_array = array();
  $except_array = array();
  $ready = fd_select($read_array, $write_array, $except_array);
  
  $data = fd_read($read_fd, 3);
  if ($data !== null) {
    echo "pipe did not report close\n";
    exit(1);
  } else {
    exit(0);
  }
} else {
  echo "failed to fork";
  exit(1);
} 