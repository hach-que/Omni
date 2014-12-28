#!/bin/omni

echo "b: bg"
usleep 50000 &
echo "b: fg"
usleep 10000
echo "b: end"

