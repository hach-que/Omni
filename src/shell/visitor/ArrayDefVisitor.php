<?php

final class ArrayDefVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $array = new ArrayContainer();
  
    foreach ($data['children'] as $child) {
      $child = $this->visitChild($shell, $child)->getCopy();
      if ($child['has-key']) {
        $array->set($child['key'], $child['value']);
      } else {
        $array->append($child['value']);
      }
    }
  
    return $array;
  }
  
}

    