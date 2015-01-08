<?php

final class SuggestionEngine extends Phobject {

  public function getSuggestions(Shell $shell, $input, $position) {
    // Parse the input.
    $tree = omnilang_parse($input);
    
    if ($tree === false) {
      return array();
    }
    
    // Traverse the tree and find the current node for the
    // specified position.
    $list = $this->traverseToPosition($tree, $position);
    
    $providers = id(new PhutilSymbolLoader())
      ->setAncestorClass('SuggestionProvider')
      ->loadObjects();
    
    $suggestions = array();
    
    for ($i = count($list) - 1; $i >= 0; $i--) {
      if ($i > 1) {
        $context = array_slice($list, 0, $i);
      } else {
        $context = array();
      }
      
      foreach ($providers as $provider) {
        $provider_suggestions = $provider->getSuggestions($shell, $list[$i], $context);
        foreach ($provider_suggestions as $suggestion) {
          $suggestions[] = $suggestion;
        }
      }
    }
    
    return isort($suggestions, 'priority');
  }
  
  public function traverseToPosition($current_node, $target, $node_start = 0, $parents = null) {
    if ($parents === null) {
      $parents = array();
    }
    
    $next = $parents;
    $next[] = $current_node;
    
    omni_trace("looking at ".$current_node['type']);
    omni_trace("parent count is ".count($parents));
    
    foreach ($current_node['children'] as $child) {
      $start = $node_start + $child['relative'];
      $end = $node_start + $child['relative'] + strlen($child['original']);
    
      omni_trace("checking child ".$child['type']);
      omni_trace("current start is: ".$start);
      omni_trace("current end is: ".$end);
      omni_trace("current target is: ".$target);
      omni_trace("current start <= target is: ".($start <= $target));
      omni_trace("current end >= target is: ".($end >= $target));
      
      if ($start <= $target && $end >= $target) {
        omni_trace("traversing to matched child...");
        return $this->traverseToPosition($child, $target, $node_start + $child['relative'], $next);
      }
    }
    
    omni_trace("no children were within the desired range, returning with self");
    
    return $next;
  }

}