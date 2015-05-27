<?php

final class HttpServeBuiltin extends Builtin {

  public function useInProcessPipes() {
    return true;
  }
  
  public function getName() {
    return 'http-serve';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    $stdout_endpoint = $stdout->createInboundEndpoint(null, "pipe builtin stdout");
    
    return array(
      'stdout' => $stdout_endpoint,
      'close_read_on_fork' => array(),
      'close_write_on_fork' => array(
        $stdout_endpoint,
      ),
    );
  }
  
  public function getDescription() {
    return <<<EOF
Handles incoming HTTP requests and dispatches them to the
Omni functions based on the request map.
EOF;
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(
      array(
        'name' => 'port',
        'short' => 'p',
        'param' => 'port',
        'help' => 
          'The port to listen on (defaults to port 8080).'
      ),
      array(
        'name' => 'rest',
        'short' => 'r',
        'help' => 
          'Serve HTTP requests as a REST API, '.
          'translating the objects to JSON objects.'
      ),
      array(
        'name' => 'map',
        'short' => 'm',
        'param' => 'map',
        'help' => 
          'The request to function map; this parameter '.
          'should be an array, with the keys regular '.
          'expressions matching paths and the values '.
          'Omni lambdas.'
      ),
    ); 
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull($this->getArguments($shell, $job, $prepare_data));
    
    if ($parser->getArg('port')) {
      $port = $parser->getArg('port');
    } else {
      $port = 8080;
    }
    
    $map = $parser->getArg('map');
    
    $stdout->write("Listening on $port");
    
    $trap = new PhutilErrorTrap();
      $socket = @socket_create_listen($port);
      $err = $trap->getErrorsAsString();
    $trap->destroy();
    
    if ($socket === false) {
      throw new Exception($err);
    }
    
    while ($conn = socket_accept($socket)) {
      $stdout->write("Accepted connection");
    
      $stdout->write("Reading HTTP request");
      $request = $this->readHTTPRequest($conn);
      
      $handled = false;
      foreach ($map as $path => $function) {
        $type_and_path = explode(':', $path, 2);
        $type = null;
        $path = null;
        if (count($type_and_path) === 1) {
          $type = 'http';
          $path = $type_and_path[0];
        } else if (count($type_and_path) === 2) {
          $type = $type_and_path[0];
          $path = $type_and_path[1];
        }
        
        $stdout->write("Trying to match ".$request['path']." with (regex) ".$path."...");
        
        if (preg_match('@^'.$path.'$@', $request['path'], $matches) === 1) {
          $stdout->write("Handling on: ".$path);
          $handled = true;
          
          switch ($type) {
            case 'http':
              $stdout->write("Handling as HTTP request");
              $result = $shell->invokeCallable($function, array($request));
              $stdout->write("Sending HTTP response");
              $this->sendHTTPResponse($conn, $result, "200 OK");
              break;
            case 'socket':
              $stdout->write("Handling as WebSocket request");
              $this->performSocketCommunications($shell, $stdout, $conn, $request, $function);
              break;
            default:
              throw new Exception('Unknown request type '.$type);
          }
          
          break;
        }
      }
      
      if (!$handled) {
        $stdout->write("Sending 404 HTTP response");
        $this->sendHTTPResponse($conn, $this->get404Message(), "404 Not Found");
      }
      
      socket_close($conn);
    }
    
    $stdout->closeWrite();
  }
  
  private function performSocketCommunications($shell, $stdout, $conn, $request, $function) {
    $stdout->write("beginning websocket communications");
  
    if (idx($request['headers'], 'Upgrade') !== 'websocket' ||
      idx($request['headers'], 'Connection') !== 'Upgrade') {
      $stdout->write("invalid request headers; terminating websocket");
    
      $this->sendHTTPResponse($conn, $this->get500Message(), "500 Internal Server Error");
      return;
    }
    
    $stdout->write("calculating token and protocol");
    
    $accept_token = idx($request['headers'], 'Sec-WebSocket-Key');
    $accept_token = $accept_token.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    $accept_token = base64_encode(sha1($accept_token, true));
    $protocol = '';
    if (isset($request['headers']['Sec-WebSocket-Protocol'])) {
      $protocols = explode(',', idx($request['headers'], 'Sec-WebSocket-Protocol'));
      $protocol = trim(head($protocols));
      $protocol = "Sec-WebSocket-Protocol: $protocol\r\n";
    }
    
    $stdout->write("accept token is $accept_token");
    $stdout->write("protocol is $protocol");
    
    $upgrade = 
      "HTTP/1.1 101 Switching Protocols\r\n".
      "Upgrade: websocket\r\n".
      "Connection: Upgrade\r\n".
      "Sec-WebSocket-Accept: $accept_token\r\n".
      $protocol.
      "\r\n";
    
    $stdout->write("writing websocket response header");
    
    socket_write($conn, $upgrade, strlen($upgrade));
    
    $stdout->write("creating socket endpoint");
    
    // Construct two endpoints that represent the read and write
    // endpoints of the socket.
    $endpoint = new WebSocketEndpoint($conn);
    
    $stdout->write("writing websocket response header");
    
    $data = array(
      'action' => $request['action'],
      'path' => $request['path'],
      'headers' => $request['headers'],
      'data' => null,
      'endpoint' => $endpoint,
    );
    
    $shell->invokeCallable($function, array($data));
  }

  private function readHTTPRequest($conn) {
    // Read data from the socket until we have at least \r\n.  Then
    // if we have Content-Length we know how much data to read after
    // the \r\n, or if it's omitted there is no additional data to read.
    $buffer = '';
    $read = 0;
    while (substr_count($buffer, "\r\n\r\n") === 0) {
      $bytes = socket_recv($conn, $tmp_buffer, 4096, MSG_DONTWAIT);
      $read += $bytes;
      $buffer .= $tmp_buffer;
    }
    
    $term_pos = strpos($buffer, "\r\n\r\n");
    $raw_headers = explode("\r\n", substr($buffer, 0, $term_pos));
    $first_line = array_shift($raw_headers);
    $headers = array();
    foreach ($raw_headers as $raw_header) {
      $header_pair = explode(":", $raw_header, 2);
      if (count($header_pair) >= 2) {
        $headers[trim($header_pair[0])] = trim($header_pair[1]);
      } else if (count($header_pair) === 1) {
        $headers[trim($header_pair[0])] = true;
      }
    }
    
    $data_bytes_read_already = $read - ($term_pos + 4);
    $data_bytes_to_read_total = 0;
    if (isset($headers['Content-Length'])) {
      $data_bytes_to_read_total = $headers['Content-Length'];
    }
    
    $data_bytes_remaining = $data_bytes_to_read_total - $data_bytes_read_already;
    $bytes = socket_recv($conn, $tmp_buffer, $data_bytes_remaining, 0);
    $buffer .= $tmp_buffer;
    
    $first_line_info = explode(' ', $first_line);
    
    return array(
      'action' => idx($first_line_info, 0),
      'path' => idx($first_line_info, 1),
      'headers' => $headers,
      'data' => substr($buffer, $term_pos + 4, $data_bytes_to_read_total),
    );
  }
  
  private function sendHTTPResponse($conn, $response, $code) {
    $content = print_r($response, true);
  
    $buffer = 
      "HTTP/1.1 $code\r\n".
      "Server: Omni shell builtin web server\r\n".
      "Content-Length: ".strlen($content)."\r\n".
      "\r\n".
      $content;
    
    socket_write($conn, $buffer, strlen($buffer));
  }
  
  private function get404Message() {
    return <<<EOF
Sorry, but the page you requested was not found!
EOF;
  }
  
}