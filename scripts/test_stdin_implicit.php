#!/usr/bin/env php
<?php

echo "starting\n";
$pid = pcntl_fork();
if ($pid === 0) {
  echo "will close stdin\n";
  fd_close(0);
  exit(0);
} else if ($pid > 0) {
  $char = fd_read(0, 1);
  if ($char === false) {
    echo idx(fd_get_error(), 'error')."\n";
  } elseif ($char === null) {
    echo "eof on stdin\n";
  } else {
    echo "success: $char\n";
  }

  sleep(1);

  $char = fd_read(0, 1);
  if ($char === false) {
    echo idx(fd_get_error(), 'error')."\n";
  } elseif ($char === null) {
    echo "eof on stdin\n";
  } else {
    echo "success: $char\n";
  }
} else {
  echo "error while forking\n";
} 