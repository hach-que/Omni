#!/bin/omni

: $map = @(
  '/' => (() => {
    return "<p>Mmmm.. shells... <a href='/test'>somewhere else</a></p>";
  }),
  'socket:/test' => (() => {
    : $request = $1

    : $endpoint = ($request->endpoint)

    : $endpoint->readFormat = 'newline-separated'
    : $endpoint->writeFormat = 'newline-separated'

    while $true {
      : $endpoint->write("blarg")

      : $msg = ($endpoint->read())

      echo "got back from read"
      echo ($msg)

      : $endpoint->write('pong')
    }
  }),
)

http-serve -m $map -p $1
