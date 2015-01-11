#!/bin/omni

: $futures = @(
  $(new -t ExecFuture "usleep %d" 1),
  $(new -t ExecFuture "usleep %d" 1000),
  $(new -t ExecFuture "usleep %d" 2000),
  $(new -t ExecFuture "usleep %d" 3000),
  $(new -t ExecFuture "usleep %d" 4000),
)

foreach $(futures $futures) as $future {
  echo ($future->command)
}

