<?php

final class NativeFDTestCase extends PhutilTestCase {

  public function testPipeBehavesAsExpected() {
  
    $pipe = fd_pipe();
    $read_fd = $pipe['read'];
    $write_fd = $pipe['write'];
    
    $pid = pcntl_fork();
    if ($pid === 0) {
      fd_close($read_fd);
      
      fd_write($write_fd, "abc");
      
      usleep(5000);
      
      fd_write($write_fd, "def");
      
      usleep(5000);
      
      fd_close($write_fd);
      omni_exit(0);
    } else if ($pid > 0) {
      fd_close($write_fd);
      
      $read_array = array($read_fd);
      $write_array = array();
      $except_array = array();
      $ready = fd_select($read_array, $write_array, $except_array);
      
      $data = fd_read($read_fd, 3);
      $this->assertEqual("abc", $data);
      
      $read_array = array($read_fd);
      $write_array = array();
      $except_array = array();
      $ready = fd_select($read_array, $write_array, $except_array);
      
      $data = fd_read($read_fd, 3);
      $this->assertEqual("def", $data);
      
      $read_array = array($read_fd);
      $write_array = array();
      $except_array = array();
      $ready = fd_select($read_array, $write_array, $except_array);
      
      $data = fd_read($read_fd, 3);
      $this->assertEqual(null, $data);
    } else {
      $this->assertFail('Failed to fork!');
    } 
  }

}