<?php

/**
 * Perform some sanity checks against the possible diversity of PHP builds in
 * the wild, like very old versions and builds that were compiled with flags
 * that exclude core functionality.
 */
function sanity_check_environment() {
  // NOTE: We don't have phutil_is_windows() yet here.
  $is_windows = (DIRECTORY_SEPARATOR != '/');

  // We use stream_socket_pair() which is not available on Windows earlier.
  $min_version = ($is_windows ? '5.3.0' : '5.2.3');
  $cur_version = phpversion();
  if (version_compare($cur_version, $min_version, '<')) {
    die_with_bad_php(
      "You are running PHP version '{$cur_version}', which is older than ".
      "the minimum version, '{$min_version}'. Update to at least ".
      "'{$min_version}'.");
  }

  if ($is_windows) {
    $need_functions = array(
      'curl_init'     => array('builtin-dll', 'php_curl.dll'),
    );
  } else {
    $need_functions = array(
      'curl_init'     => array(
        'text',
        "You need to install the cURL PHP extension, maybe with ".
        "'apt-get install php5-curl' or 'yum install php53-curl' or ".
        "something similar."),
      'json_decode'   => array('flag', '--without-json'),
    );
  }

  $problems = array();

  $config = null;
  $show_config = false;
  foreach ($need_functions as $fname => $resolution) {
    if (function_exists($fname)) {
      continue;
    }

    static $info;
    if ($info === null) {
      ob_start();
      phpinfo(INFO_GENERAL);
      $info = ob_get_clean();
      $matches = null;
      if (preg_match('/^Configure Command =>\s*(.*?)$/m', $info, $matches)) {
        $config = $matches[1];
      }
    }

    $generic = true;
    list($what, $which) = $resolution;

    if ($what == 'flag' && strpos($config, $which) !== false) {
      $show_config = true;
      $generic = false;
      $problems[] =
        "This build of PHP was compiled with the configure flag '{$which}', ".
        "which means it does not have the function '{$fname}()'. This ".
        "function is required for omni to run. Rebuild PHP without this flag. ".
        "You may also be able to build or install the relevant extension ".
        "separately.";
    }

    if ($what == 'builtin-dll') {
      $generic = false;
      $problems[] =
        "Your install of PHP does not have the '{$which}' extension enabled. ".
        "Edit your php.ini file and uncomment the line which reads ".
        "'extension={$which}'.";
    }

    if ($what == 'text') {
      $generic = false;
      $problems[] = $which;
    }

    if ($generic) {
      $problems[] =
        "This build of PHP is missing the required function '{$fname}()'. ".
        "Rebuild PHP or install the extension which provides '{$fname}()'.";
    }
  }

  if ($problems) {
    if ($show_config) {
      $problems[] = "PHP was built with this configure command:\n\n{$config}";
    }
    die_with_bad_php(implode("\n\n", $problems));
  }
}

function die_with_bad_php($message) {
  echo "\nPHP CONFIGURATION ERRORS\n\n";
  echo $message;
  echo "\n\n";
  exit(1);
}
