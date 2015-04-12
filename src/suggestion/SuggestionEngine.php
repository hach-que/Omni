<?php

final class SuggestionEngine extends Phobject {

  public function getSuggestions(Shell $shell, $input, $position) {
    omni_trace("getSuggestions: start");
  
    // Parse the input.
    $tree = omnilang_parse($input);
    
    if ($tree === false) {
      return array();
    }
    
    omni_trace("getSuggestions: before traverseToPosition");
    
    // Traverse the tree and find the current node for the
    // specified position.
    $list = $this->traverseToPosition($tree, $position);
    
    omni_trace("getSuggestions: before symbol load");
    
    $providers = id(new PhutilSymbolLoader())
      ->setAncestorClass('SuggestionProvider')
      ->loadObjects();
    
    $suggestions = array();
    
    omni_trace("getSuggestions: before position detect");
    
    for ($i = count($list) - 1; $i >= 0; $i--) {
      if ($i > 1) {
        $context = array_slice($list, 0, $i);
      } else {
        $context = array();
      }
      
      omni_trace("getSuggestions: load suggestions");
      
      foreach ($providers as $provider) {
        try {
          omni_trace("getSuggestions: call getSuggestions ".get_class($provider));
          $provider_suggestions = $provider->getSuggestions($shell, $list[$i], $context);
          foreach ($provider_suggestions as $suggestion) {
            omni_trace("getSuggestions: add suggestion ".$suggestion['append']);
            if ($suggestion['append'] !== '') {
              $suggestions[] = $suggestion;
            }
          }
        } catch (EvaluationWouldCauseSideEffectException $ex) {
          // This provider can't give suggestions because the evaluation would
          // cause a side-effect.
        }
      }
    }
    
    omni_trace("getSuggestions: sort results by priority");
    
    $sorted_suggestions = array();
    $groups = igroup($suggestions, 'priority');
    foreach ($groups as $priority => $suggestions) {
      $s = isort($suggestions, 'length');
      foreach ($s as $a) {
        $sorted_suggestions[] = $a;
      }
    }
    return $sorted_suggestions;
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