<?php

final class OmniTrace extends Phobject {

  private static $isTracing = false;
  private static $traceDirectory = null;
  private static $showOnStderr = false;
  
  public static function enableTracing($show_on_stderr = true) {
    self::$isTracing = true;
    self::$traceDirectory = getcwd();
    self::$showOnStderr = $show_on_stderr;
    
    if ($show_on_stderr) {
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
  }
  
  public static function isTracing() {
    return self::$isTracing;
  }
  
  public static function getTraceDirectory() {
    return self::$traceDirectory;
  }
  
  public static function isShowingOnStderr() {
    return self::$showOnStderr;
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
    $timestamp = omni_timestamp64();
    $file = @fopen(OmniTrace::getTraceDirectory()."/".$pid.".trace", 'a');
    if ($file) {
      fwrite($file, $message."\n");
      fclose($file);
    }

    if (OmniTrace::isShowingOnStderr()) {
      static $stderr;
      $stderr = fopen('php://stderr', 'w+');
      fwrite($stderr, $pid." ".$timestamp.": ".$message."\n");
    }
  }
}