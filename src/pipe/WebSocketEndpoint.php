<?php

final class WebSocketEndpoint extends BaseEndpoint {

  private $socket;
  private $isBlocking;
  private $unreadBytes = '';
  
  public function __construct($socket) {
    $this->socket = $socket;
    $this->isBlocking = true;
    socket_set_block($this->socket);
  }
  
  public function getWriteFormat() {
    return $this->writeFormat;
  }
  
  public function getReadFormat() {
    return $this->readFormat;
  }
  
  public function setWriteFormat($format) {
    $this->writeFormat = $format;
    return $this;
  }
  
  public function setReadFormat($format) {
    $this->readFormat = $format;
    return $this;
  }
  
  
  /* ----- ( BaseEndpoint ) ------------------------------------------------- */
  
  
  const FIN_ON = 0x80;
  const MASK_ON = 0x80;
  const OPCODE_CONTINUE = 0x00;
  const OPCODE_UTF8 = 0x01;
  const OPCODE_BINARY = 0x02;
  const OPCODE_TERMINATE = 0x08;
  const OPCODE_PING = 0x09;
  const OPCODE_PONG = 0x10;
  
  protected function writeInternal($data) {
    $type = self::OPCODE_UTF8;
    if (strpos($data, "\0") !== false) {
      $type = self::OPCODE_BINARY;
    } else {
      $data = utf8_encode($data);
    }
  
    list($header, $data) = $this->constructWebSocketHeader($data, $type);
  
    socket_write($this->socket, $header, strlen($header));
  
    return socket_write($this->socket, $data, strlen($data));
  }
  
  protected function setReadBlockingInternal($blocking) {
    if ($blocking) {
      socket_set_block($this->socket);
    } else {
      socket_set_nonblock($this->socket);
    }
    
    $this->isBlocking = $blocking;
  }
    
  protected function readInternal($length) {
    if ($length <= strlen($this->unreadBytes)) {
      omni_trace("enough bytes in unread buffer to return data");
      
      $result = substr($this->unreadBytes, 0, $length);
      if ($length === strlen($this->unreadBytes)) {
        $this->unreadBytes = '';
      } else {
        $this->unreadBytes = substr($this->unreadBytes, $length);
      }
      
      omni_trace("returning \"".$result."\"");
      return $result;
    }
  
    $first_bytes = socket_read($this->socket, 2);
    
    if (!$this->isBlocking) {
      omni_trace("non-blocking read from websocket..");
    } else {
      omni_trace("blocking read from websocket..");
    }
    
    if (strlen($first_bytes) < 2 && !$this->isBlocking) {
      return '';
    }
    
    omni_trace("got first 2 bytes: ".bin2hex($first_bytes));
    
    socket_set_block($this->socket);
    
    // TODO Support fragmented packets, because WebSockets is derp.
    if ((ord($first_bytes[0]) & self::FIN_ON) !== self::FIN_ON) {
      throw new Exception('Fragmented packet, I can\'t handle this yet!');
    }
    
    $opcode = ord($first_bytes[0]) & 0xF;
    switch ($opcode) {
      case self::OPCODE_CONTINUE:
        throw new Exception('Continue packet, I can\'t handle this yet!');
      case self::OPCODE_UTF8:
      case self::OPCODE_BINARY:
      case self::OPCODE_PING:
      case self::OPCODE_PONG:
        $mode = $opcode;
        break;
      case self::OPCODE_TERMINATE:
        socket_close($this->socket);
        throw new NativePipeClosedException();
      default:
        throw new Exception('Unknown opcode '.$opcode);
    }
    
    $mask = false;
    if (ord($first_bytes[1]) & self::MASK_ON) {
      omni_trace("data is masked");
      $mask = true;
    }
    
    $payload_len = ord($first_bytes[1]) & 0x7F;
    if ($payload_len <= 125) {
      // This is the real payload length.
    } else if ($payload_len === 126) {
      // We must read another two bytes for 16-bit length.
      $length_bytes = socket_read($this->socket, 2);
      $payload_len =
        (ord($length_bytes[0]) << 8) |
        ord($length_bytes[1]);
    } else if ($payload_len === 127) {
      // We must read another eight bytes for 64-bit length.
      $length_bytes = socket_read($this->socket, 8);
      $payload_len =
        (ord($length_bytes[0]) << 56) |
        (ord($length_bytes[1]) << 48) |
        (ord($length_bytes[2]) << 40) |
        (ord($length_bytes[3]) << 32) |
        (ord($length_bytes[4]) << 24) |
        (ord($length_bytes[5]) << 16) |
        (ord($length_bytes[6]) << 8) |
        ord($length_bytes[7]);
    }
    
    omni_trace("payload length is ".$payload_len);
    
    if ($mask) {
      $mask_bytes = socket_read($this->socket, 4);
    }
    
    omni_trace("starting read of payload");
    
    $current = 0;
    $buffer = '';
    while ($current < $payload_len) {
      $bytes = socket_read($this->socket, min(8192, $payload_len - $current));
      $buffer .= $bytes;
      $current += strlen($bytes);
    }
    
    if ($mask !== false) {
      omni_trace("unmasking");
      
      for ($i = 0; $i < strlen($buffer); $i++) {
        $buffer[$i] = chr(ord($buffer[$i]) ^ ord($mask_bytes[$i % 4]));
      }
    }
    
    omni_trace("read $current bytes");
    
    if (!$this->isBlocking) {
      omni_trace("restoring non-blocking mode");
      socket_set_nonblock($this->socket);
    }
    
    switch ($mode) {
      case self::OPCODE_UTF8:
        omni_trace("content was utf8 data; decoding");
        $buffer = utf8_decode($buffer);
        break;
      case self::OPCODE_BINARY:
        omni_trace("content was binary data, leaving it as is");
        break;
      case self::OPCODE_PING:
        omni_trace("was a ping messages, sending pong");
        
        // We have to write the pong message back and restart
        // the read operation.
        list($header, $data) = 
          $this->constructWebSocketHeader(array(), self::OPCODE_PONG);
        socket_write($this->socket, $header, strlen($header));
        
        omni_trace("restarting read request");
        return $this->readInternal($length);
      case self::OPCODE_PONG:
        omni_trace("was a pong message");
        
        // We ignore this message (it doesn't mean anything to us)
        // and then restart the read operation.
        omni_trace("restarting read request");
        return $this->readInternal($length);
    }
  
    omni_trace("placing buffer into unread buffer");
    
    $this->unreadBytes .= $buffer;
    
    omni_trace("restarting read request to potentially return data");
    
    return $this->readInternal($length);
  }
  
  public function close() {
    list($header, $data) = 
      $this->constructWebSocketHeader(array(), self::OPCODE_TERMINATE);
    socket_write($this->socket, $header, strlen($header));
    socket_close($this->socket);
  }
  
  
  /* ----- ( Header Construction and Parsing ) ------------------------------ */
  
  
  private function constructWebSocketHeader($data, $type) {
    $length = strlen($data);
  
    // According to error messages displayed by Chrome, servers
    // should not mask any messages to the client.  If you have some
    // usage that does require masking, changing this to true will
    // enable the appropriate functionality.
    $should_mask = false;
  
    $fin_rsv_opcode = 0x0;
    $fin_rsv_opcode |= self::FIN_ON;
    $fin_rsv_opcode |= $type;
    
    if ($should_mask) {
      $mask_payload_len = self::MASK_ON;
    } else {
      $mask_payload_len = 0;
    } 
    
    if ($length <= 125) {
      $mask_payload_len |= $length;
      $payload_bytes = array();
    } else if ($length < 0x10000) {
      $mask_payload_len |= 126;
      $payload_bytes = array(
        ($length >> 8) & 0xFF,
        $length & 0xFF,
      );
    } else {
      $mask_payload_len |= 127;
      $payload_bytes = array(
        ($length >> 56) & 0xFF,
        ($length >> 48) & 0xFF,
        ($length >> 40) & 0xFF,
        ($length >> 32) & 0xFF,
        ($length >> 24) & 0xFF,
        ($length >> 16) & 0xFF,
        ($length >> 8) & 0xFF,
        $length & 0xFF,
      );
    }
  
    if ($should_mask) {
      $masking_key = rand();
      $masking_key_bytes = array(
        ($masking_key >> 24) & 0xFF,
        ($masking_key >> 16) & 0xFF,
        ($masking_key >> 8) & 0xFF,
        $masking_key & 0xFF,
      );
    } else {
      $masking_key_bytes = array();
    }
    
    $websocket_header = array(
      $fin_rsv_opcode,
      $mask_payload_len,
    ) + $payload_bytes + $masking_key_bytes;
  
    if ($should_mask) {
      for ($i = 0; $i < $length; $i++) {
        $data[$i] = chr(ord($data[$i]) ^ ord($masking_key_bytes[$i % 4]));
      }
    }
  
    $header_bytes = '';
    for ($i = 0; $i < count($websocket_header); $i++) {
      $header_bytes .= chr($websocket_header[$i]);
    }
    
    return array($header_bytes, $data);
  }
  
}