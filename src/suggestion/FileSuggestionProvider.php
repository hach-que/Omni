<?php

final class FileSuggestionProvider extends SuggestionProvider {

  public function getSuggestions(Shell $shell, $current, $context) {
    if ($current['type'] !== 'fragments') {
      return array();
    }
    
    $visitor = id(new FragmentsVisitor())
      ->setAllowSideEffects(false);
    $current_value = 
      $visitor->visit($shell, $current);
    $safe_to_append = 
      $visitor->isSafeToAppendFragment($shell, $current); 
     
    if (!is_string($current_value)) {
      return array();
    }
     
    if (strlen($current_value) === 0) {
      return array();
    }
     
    $components = explode("/", $current_value);
    $last_component = array_pop($components);
    $base = implode("/", $components);
    
    $expander = new ExpressionExpander();
    $base = $expander->expandFilePath($base);
    $original = $expander->expandFilePath($current_value);
    
    if (strlen($base) !== 0) {
      if (!(@Filesystem::pathExists($base))) {
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
      $entries = @Filesystem::listDirectory($base);
    } catch (Exception $ex) {
      return array();
    }
    
    $results = array();
    
    foreach ($entries as $entry) {
      if (strlen($entry) >= strlen($last_component)) {
        if (substr($entry, 0, strlen($last_component)) === $last_component) {
          $append = substr($entry, strlen($last_component));
          if (!$safe_to_append) {
            $append = str_replace(" ", "\\ ", $append); // TODO Make this nicer
          }
          $type = 'file';
          if (is_dir($original.$append)) {
            $append .= '/';
            $type = 'directory';
          }
          $results[] = array(
            'append' => $append,
            'node_replace' => $current['original'].$append,
            'length' => strlen($current['original'].$append),
            'description' => 'existing '.$type,
            'priority' => 1000,
            'wrap_quotes' => !$safe_to_append,
          );
        }
      }
    }
    
    return $results;
  }

}