<?php

final class ExpressionExpander extends Phobject {

  public function expandFilePath($path) {
    if (!is_string($path)) {
      return $path;
    }
  
    if (strlen($path) === 0) {
      return $path;
    }
  
    $home = getenv('HOME');
    if ($home !== null) {
      if (strlen($path) >= 2 && substr($path, 0, 2) === '~/') {
        $new_path = $home.'/'.substr($path, 2);
        return $this->expandFilePath($new_path);
      }
    }
    
    $results = glob($path, GLOB_MARK | GLOB_NOCHECK | GLOB_BRACE);
    if (count($results) === 0) {
      return null;
    } else if (count($results) === 1) {
      return head($results);
    } else {
      return $results;
    }
  }
  
}