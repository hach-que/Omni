#!/bin/omni

iter IMG_1138.dng | () => { return $(new -t ExecFuture "convert %s %s.png" $_ $_) }

: $capture = $(iter IMG_1138.dng | () => { return $(new -t ExecFuture "convert %s %s.png" $_ $_) })