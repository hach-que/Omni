<?php

/**
 * Adjust 'include_path' to add locations where we'll search for libphutil.
 * We look in these places:
 *
 *  - Next to 'omni/'.
 *  - Anywhere in the normal PHP 'include_path'.
 *  - Inside 'omni/externals/includes/'.
 *
 * When looking in these places, we expect to find a 'libphutil/' directory.
 */
function omni_adjust_php_include_path() {
  // The 'omni/' directory.
  $omni_dir = dirname(dirname(__FILE__));

  // The parent directory of 'omni/'.
  $parent_dir = dirname($omni_dir);

  // The 'omni/externals/includes/' directory.
  $include_dir = implode(
    DIRECTORY_SEPARATOR,
    array(
      $omni_dir,
      'externals',
      'includes',
    ));

  $php_include_path = ini_get('include_path');
  $php_include_path = implode(
    PATH_SEPARATOR,
    array(
      $parent_dir,
      $php_include_path,
      $include_dir,
    ));

  ini_set('include_path', $php_include_path);
}
omni_adjust_php_include_path();

if (getenv('OMNI_PHUTIL_PATH')) {
  @include_once getenv('OMNI_PHUTIL_PATH').'/scripts/__init_script__.php';
} else {
  @include_once 'libphutil/scripts/__init_script__.php';
}
if (!@constant('__LIBPHUTIL__')) {
  echo "ERROR: Unable to load libphutil. Put libphutil/ next to omni/, or ".
       "update your PHP 'include_path' to include the parent directory of ".
       "libphutil/, or symlink libphutil/ into omni/externals/includes/.\n";
  exit(1);
}

phutil_load_library(dirname(dirname(__FILE__)).'/src/');
