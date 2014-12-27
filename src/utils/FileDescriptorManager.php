<?php

/**
 * This class abstracts and manages file descriptors.  Normally when file
 * descriptors are closed, their IDs will be re-used when new file
 * descriptors are created, but this makes it hard for us to track when
 * file descriptors are being used incorrectly.
 * 
 * To address this, this class hides the real file descriptor behind an
 * ID that always increases.  This ensures that we never accidentally
 * re-use a file descriptor, and allows us to catch scenarios where we
 * attempt to close a file descriptor twice, or perform the wrong
 * operation on a file descriptor.
 */
final class FileDescriptorManager extends Phobject {

  const STDIN_FILENO = 501;
  const STDOUT_FILENO = 502;
  const STDERR_FILENO = 503;
  
  private static $fdTable = array(
    501 => array(
      'name' => 'stdin',
      'type' => 'read',
      'fd' => 0,
    ),
    502 => array(
      'name' => 'stdout',
      'type' => 'write',
      'fd' => 1,
    ),
    503 => array(
      'name' => 'stderr',
      'type' => 'write',
      'fd' => 2,
    ),
  );
  private static $fdID = 1000;
  
  public static function replaceStandardPipes($stdin, $stdout, $stderr) {
    self::$fdTable[self::STDIN_FILENO]['fd'] = 
      self::$fdTable[$stdin]['fd'];
    self::$fdTable[self::STDOUT_FILENO]['fd'] = 
      self::$fdTable[$stdout]['fd'];
    self::$fdTable[self::STDERR_FILENO]['fd'] = 
      self::$fdTable[$stderr]['fd'];
  }
  
  public static function createPipe($read_name, $write_name) {
    $pipe = fd_pipe();
    if ($pipe === false) {
      throw new Exception('Creation of pipe failed.');
    }
    
    $read_id = self::$fdID++;
    $write_id = self::$fdID++;
    self::$fdTable[$read_id] = array(
      'name' => $read_name,
      'type' => 'read',
      'fd' => $pipe['read'],
    );
    self::$fdTable[$write_id] = array(
      'name' => $write_name,
      'type' => 'write',
      'fd' => $pipe['write'],
    );
    
    omni_trace(
      'fdmanager: opened pipe (read) '.
      self::getFDRepr($read_id).' -> '.
      self::getFDRepr($write_id).' (write)');
      
    return array(
      'read' => $read_id,
      'write' => $write_id,
    );
  }
  
  public static function createControlPipe($read_name, $write_name) {
    $pipe = fd_control_pipe();
    if ($pipe === false) {
      throw new Exception('Creation of control pipe failed.');
    }
    
    $read_id = self::$fdID++;
    $write_id = self::$fdID++;
    self::$fdTable[$read_id] = array(
      'name' => $read_name,
      'type' => 'control-read',
      'fd' => $pipe['read'],
    );
    self::$fdTable[$write_id] = array(
      'name' => $write_name,
      'type' => 'control-write',
      'fd' => $pipe['write'],
    );
    
    omni_trace(
      'fdmanager: opened control pipe (read) '.
      self::getFDRepr($read_id).' -> '.
      self::getFDRepr($write_id).' (write)');
    
    return array(
      'read' => $read_id,
      'write' => $write_id,
    );
  }
  
  public static function close($fd) {
    self::assertFDExists($fd);
    
    omni_trace('fdmanager: closing '.self::getFDRepr($fd));
    
    fd_close(self::$fdTable[$fd]['fd']);
    unset(self::$fdTable[$fd]);
  }
  
  public static function closeAll() {
    foreach (self::$fdTable as $fd => $data) {
      if ($fd >= 1000) {
        self::close($fd);
        unset(self::$fdTable[$fd]);
      }
    }
  }
  
  public static function getNativeFD($fd) {
    self::assertFDExists($fd);
    
    return self::$fdTable[$fd]['fd'];
  }
  
  public static function setBlocking($fd, $blocking) {
    self::assertFDExists($fd);
    
    omni_trace('fdmanager: changing blocking mode for '.self::getFDRepr($fd));
    
    fd_set_blocking(self::$fdTable[$fd]['fd'], $blocking);
  }
  
  public static function select($read_fds, $write_fds, $except_fds) {
    $all_fds = array_unique($read_fds + $write_fds + $except_fds);
    $all_fds_names = array();
    
    $real_read_fds = array();
    $real_write_fds = array();
    $real_except_fds = array();
    
    foreach ($read_fds as $fd) {
      self::assertFDExists($fd);
      $all_fds_names[] = self::getFDRepr($fd);
      $real_read_fds[] = self::$fdTable[$fd]['fd'];
    }
    foreach ($write_fds as $fd) {
      self::assertFDExists($fd);
      $all_fds_names[] = self::getFDRepr($fd);
      $real_write_fds[] = self::$fdTable[$fd]['fd'];
    }
    foreach ($except_fds as $fd) {
      self::assertFDExists($fd);
      $all_fds_names[] = self::getFDRepr($fd);
      $real_except_fds[] = self::$fdTable[$fd]['fd'];
    }
    
    $all_fds_names = implode(', ', $all_fds_names);
    omni_trace('fdmanager: selecting over FDs '.$all_fds_names);
    
    $result = fd_select(
      $real_read_fds,
      $real_write_fds,
      $real_except_fds);
    $streams_ready = idx($result, 'ready');
    $real_read_fds = idx($result, 'read');
    $real_write_fds = idx($result, 'write');
    $real_except_fds = idx($result, 'except');
    $read_fds = array();
    $write_fds = array();
    $except_fds = array();
    
    foreach (self::$fdTable as $fd => $info) {
      if (idx($real_read_fds, $info['fd'], false)) {
        $read_fds[$fd] = idx($real_read_fds, $info['fd'], false);
      }
      if (idx($real_write_fds, $info['fd'], false)) {
        $write_fds[$fd] = idx($real_write_fds, $info['fd'], false);
      }
      if (idx($real_except_fds, $info['fd'], false)) {
        $except_fds[$fd] = idx($real_except_fds, $info['fd'], false);
      }
    }
    
    return array(
      'ready' => $streams_ready,
      'read' => $read_fds,
      'write' => $write_fds,
      'except' => $except_fds,
    );
  }
  
  public static function read($fd, $length) {
    self::assertFDExists($fd);
    
    if (self::$fdTable[$fd]['type'] !== 'read') {
      throw new Exception(
        'Attempt to read from file descriptor '.
        $fd.' which is of type '.self::$fdTable[$fd]['type'].'!');
    }
    
    omni_trace('fdmanager: reading '.$length.' bytes from '.self::getFDRepr($fd));
    
    $result = fd_read(self::$fdTable[$fd]['fd'], $length);
    
    if ($result === false) {
      $error = fd_get_error();
      if ($error['errno'] === 5 /* EIO */ && self::$fdTable[$fd]['fd'] === 0) {
        throw new EIOWhileReadingStdinException();
      } else {
        throw new Exception('error: read on FD '.$fd.' (native '.self::$fdTable[$fd]['fd'].'): '.$error['error']);
      }
    } else if ($result === null) {
      throw new NativePipeClosedException();
    }
    
    return $result;
  }
  
  public static function write($fd, $data) {
    self::assertFDExists($fd);
    
    if (self::$fdTable[$fd]['type'] !== 'write') {
      throw new Exception(
        'Attempt to write to file descriptor '.
        $fd.' which is of type '.self::$fdTable[$fd]['type'].'!');
    }
    
    omni_trace('fdmanager: write '.strlen($data).' bytes from '.self::getFDRepr($fd));
    
    $buffer = $data;
    $to_write = strlen($data);
    $written = 0;
    do {
      $result = fd_write(self::$fdTable[$fd]['fd'], $buffer);
      if ($result === false) {
        // Error.
        throw new Exception('error: write: '.idx(fd_get_error(), 'error'));
      } else if ($result === true) {
        // Non-blocking; not ready for write.
        usleep(5000);
      } else if ($result === null) {
        // Pipe closed.
        throw new NativePipeClosedException();
      } else {
        // Wrote $result bytes.
        $written += $result;
        if ($written < $to_write) {
          $buffer = substr($data, $written);
        }
      }
    } while ($written < $to_write);
  }
  
  public static function receiveFD($fd, $new_fd_name, $new_fd_type) {
    self::assertFDExists($fd);
    
    if (self::$fdTable[$fd]['type'] !== 'control-read') {
      throw new Exception(
        'Attempt to control read from file descriptor '.
        $fd.' which is of type '.self::$fdTable[$fd]['type'].'!');
    }
    
    omni_trace('fdmanager: reading FD from '.self::getFDRepr($fd));
    
    $result = fd_control_readfd(self::$fdTable[$fd]['fd']);
    if ($result === false) {
      throw new Exception('error: recvfd: '.idx(fd_get_error(), 'error'));
    }
    
    $recv_id = self::$fdID++;
    self::$fdTable[$recv_id] = array(
      'name' => $new_fd_name,
      'type' => $new_fd_type,
      'fd' => $result,
    );
    
    return $recv_id;
  }
  
  public static function sendFD($fd, $fd2) {
    self::assertFDExists($fd);
    self::assertFDExists($fd2);
    
    if (self::$fdTable[$fd]['type'] !== 'control-write') {
      throw new Exception(
        'Attempt to control write from file descriptor '.
        $fd.' which is of type '.self::$fdTable[$fd]['type'].'!');
    }
    
    omni_trace(
      'fdmanager: writing FD '.self::getFDRepr($fd2).
      ' to '.self::getFDRepr($fd));
    
    $result = fd_control_writefd(
      self::$fdTable[$fd]['fd'],
      self::$fdTable[$fd2]['fd']);
    if ($result === false) {
      throw new Exception('error: sendfd: '.idx(fd_get_error(), 'error'));
    }
  }
  
  public static function assertFDExists($fd) {
    if (!isset(self::$fdTable[$fd])) {
      throw new Exception(
        'Attempt to access file descriptor '.$fd.' that doesn\'t exist!');
    }
  }
  
  public static function getFDRepr($fd) {
    return '('.$fd.' '.self::$fdTable[$fd]['name'].')';
  }

}