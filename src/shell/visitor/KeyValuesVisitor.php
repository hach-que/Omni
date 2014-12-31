<?php

final class KeyValuesVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $options = array();
    
    foreach ($data['children'] as $child) {
      $option = $this->visitChild($shell, $child);
      $options[$option['key']] = $option['value'];
    }
    
    return $options;
  }
  
}

    