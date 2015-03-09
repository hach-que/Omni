#!/bin/omni

: $arr = @()
for 1 150 1 as $v {
  : $arr->[] = $v
}

iter $arr | () => ($_)

