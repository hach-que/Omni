#!/bin/omni

: $test = @()
: $test->[] = "hello"
: $test->[] = "second"

foreach $test as $value {
  echo $value
}

