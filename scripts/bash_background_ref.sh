#!/bin/bash

echo "-- 1"
cat test.sh &
echo $?
echo "-- 2"
cat test.sh | grep hello &
echo $?
echo "-- 3"

sleep 10 &
echo "-- 4"

jobs

echo "-- 5"
fg 3
