#!/bin/omni

: $map = @(
  '/' => (() => {
    return "<p>Mmmm.. shells... <a href='/test'>somewhere else</a></p>";
  }),
  '/test' => (() => {
    return "<p><em>another page <a href='/'>home again</a></em></p>";
  }),
)

http-serve -m $map -p $1
