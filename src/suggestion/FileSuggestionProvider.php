<?php

final class FileSuggestionProvider extends SuggestionProvider {

  public function getSuggestions(Shell $shell, $current, $context) {
    if ($current['type'] !== 'fragments') {
      return array();
    }
    
    $current_value = id(new FragmentsVisitor())
      ->setAllowSideEffects(false)
      ->visit($shell, $current);
      
    $components = explode("/", $current_value);
    $last_component = array_pop($components);
    $base = implode("/", $components);
    
    if (strlen($base) !== 0) {
      if (!Filesystem::pathExists($base)) {
        return array();
      }
    }
    
    if (strlen($base) === 0) {
      if ($current_value[0] === '/') {
        $base = '/';
      } else {
        $base = '.';
      }
    }
    
    try {
      $entries = Filesystem::listDirectory($base);
    } catch (Exception $ex) {
      return array();
    }
    
    $results = array();
    
    foreach ($entries as $entry) {
      if (strlen($entry) >= strlen($last_component)) {
        if (substr($entry, 0, strlen($last_component)) === $last_component) {
          $append = substr($entry, strlen($last_component));
          $append = str_replace(" ", "' '", $append); // TODO Make this nicer
          if (is_dir($current['original'].$append)) {
            $append .= '/';
          }
          $results[] = array(
            'append' => $append,
            'node_replace' => $current['original'].$append,
            'description' => 'existing file',
            'priority' => 1000,
          );
        }
      }
    }
    
    return $results;
  }

}