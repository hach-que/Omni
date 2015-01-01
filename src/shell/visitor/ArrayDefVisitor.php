<?php

final class ArrayDefVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $array = array();
  
    foreach ($data['children'] as $child) {
      $child = $this->visitChild($shell, $child);
      if ($child['has-key']) {
        $array[$child['key']] = $child['value'];
      } else {
        $array[] = $child['value'];
      }
    }
  
    return $array;
  }
  
}

    