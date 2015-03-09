#!/bin/omni

: $arr = @()
for 1 150 1 as $v {
  : $arr->[] = $v
}

: $results = $(iter $arr | () => ($_))

echo $results

