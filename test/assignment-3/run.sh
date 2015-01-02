#!/bin/omni

: $test = @()
: $test->0 = "hello"
: $test->1 = "second"
echo ($test->0)
echo ($test->1)
