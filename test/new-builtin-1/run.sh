#!/bin/omni

: $name = "Exception";
: $test = $(new -t $name "abc");
echo ($test->message);
