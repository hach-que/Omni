#!/bin/omni

echo "a: bg"
./second.sh &
usleep 200000
echo "a: fg"
./second.sh
echo "a: end"

