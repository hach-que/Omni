<?php

final class OmniTrace extends Phobject {

  private static $isTracing = false;
  
  public static function enableTracing() {
    self::$isTracing = true;
    
    if (function_exists("editline_enable_tracing")) {
      editline_enable_tracing();
    }
    if (function_exists("fd_enable_tracing")) {
      fd_enable_tracing();
    }
    if (function_exists("omni_enable_tracing")) {
      omni_enable_tracing();
    }
    if (function_exists("omnilang_enable_tracing")) {
      omnilang_enable_tracing();
    }
    if (function_exists("tc_enable_tracing")) {
      tc_enable_tracing();
    }
  }
  
  public static function isTracing() {
    return self::$isTracing;
  }

}

function omni_exit($code) {
  if (OmniTrace::isTracing()) {
    sleep(1);
    omni_trace("Omni is now exiting with error code $code.");
    sleep(1);
    $trace = debug_backtrace();
    print_r($trace);
    sleep(1);
    omni_trace("Goodbye!");
    sleep(1);
    exit($code);
  } else {
    exit($code);
  }
}

function omni_trace($message) {
  if (OmniTrace::isTracing()) {
    $pid = posix_getpid();
    $file = fopen($pid.".trace", 'a');
    fwrite($file, $message."\n");
    fclose($file);

    fd_write(2, $pid.": ".$message."\n");
  }
}