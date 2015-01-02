#!/bin/omni

: $test = @()
: $test->[] = "hello"
: $test->[] = "second"
echo ($test->0)
echo ($test->1)
