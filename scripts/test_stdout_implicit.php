#!/usr/bin/env php
<?php

echo "starting\n";
$pid = pcntl_fork();
if ($pid === 0) {
  echo "will close stdout\n";
  fd_close(1);
  exit(0);
} else if ($pid > 0) {
  echo "writing before sleep\n";
  sleep(1);
  echo "writing after sleep\n";
} else {
  echo "error while forking\n";
} 