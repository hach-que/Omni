#!/bin/omni

: $arr = @()
for 1 150 1 as $v {
  : $arr->[] = $v
}

#iter $arr | () => { return $(new -t ExecFuture "echo %s" $_) }

echo "Constructing futures..."
: $futures = $(iter $arr | () => { return $(new -t ExecFuture "usleep %s" $_) })

echo "Executing futures..."
foreach $(futures $futures) as $v {
  echo ($v->command)
}

