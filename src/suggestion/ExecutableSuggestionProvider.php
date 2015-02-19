<?php

final class ExecutableSuggestionProvider extends SuggestionProvider {

  public function getSuggestions(Shell $shell, $current, $context) {
    if ($current['type'] !== 'fragments') {
      return array();
    }
    
    $arguments = array_pop($context);
    if ($arguments['type'] !== 'arguments') {
      return array();
    }
    
    $command = array_pop($context);
    if ($command['type'] !== 'command') {
      return array();
    }
    
    if ($arguments['children'][0] !== $current) {
      return array();
    }
    
    $visitor = id(new FragmentsVisitor())
      ->setAllowSideEffects(false);
    $last_component = 
      $visitor->visit($shell, $arguments['children'][0]);
    $safe_to_append = 
      $visitor->isSafeToAppendFragment($shell, $arguments['children'][0]); 
    
    if (!is_string($last_component)) {
      return array();
    }
    
    $paths = explode(':', getenv('PATH'));
    
    $results = array();
    $add_entries = array();
    
    foreach ($paths as $path) {
      $entries = Filesystem::listDirectory($path);
      foreach ($entries as $entry) {
        if (is_executable($path.'/'.$entry)) {
          if (strlen($entry) >= strlen($last_component)) {
            if (substr($entry, 0, strlen($last_component)) === $last_component) {
              $append = substr($entry, strlen($last_component));
              $append = str_replace(" ", "' '", $append); // TODO Make this nicer
              if (array_key_exists($append, $add_entries)) {
                continue;
              }
              $add_entries[$append] = true;
              $results[] = array(
                'append' => $append,
                'node_replace' => $current['original'].$append,
                'description' => 'executable',
                'priority' => 2000,
                'wrap_quotes' => !$safe_to_append,
              );
            }
          }
        }
      }
    }
    
    return $results;
  }

}