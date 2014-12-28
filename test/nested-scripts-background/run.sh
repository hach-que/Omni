#!/home/james/Projects/Omni/omni/bin/omni

echo "a: bg"
./second.sh &
sleep 5
echo "a: fg"
./second.sh
echo "a: end"

