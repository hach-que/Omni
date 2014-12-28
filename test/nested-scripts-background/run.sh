#!/home/james/Projects/Omni/omni/bin/omni

echo "a: bg"
./second.sh &
echo "a: fg"
./second.sh
echo "a: end"

