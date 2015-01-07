<?php

final class OmniTrace extends Phobject {

  private static $isTracing = false;
  private static $traceDirectory = null;
  
  public static function enableTracing() {
    self::$isTracing = true;
    self::$traceDirectory = getcwd();
    
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
  
  public static function getTraceDirectory() {
    return self::$traceDirectory;
  }

}

function omni_exit($code) {
  if (OmniTrace::isTracing()) {
    omni_trace("omni with error code $code");
    exit($code);
  } else {
    exit($code);
  }
}

function omni_trace($message) {
  if (OmniTrace::isTracing()) {
    $pid = posix_getpid();
    $file = @fopen(OmniTrace::getTraceDirectory()."/".$pid.".trace", 'a');
    if ($file) {
      fwrite($file, $message."\n");
      fclose($file);
    }

    static $stderr;
    $stderr = fopen('php://stderr', 'w+');
    fwrite($stderr, $pid.": ".$message."\n");
  }
}