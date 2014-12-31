<?php

final class KeyValueVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $key = $data['data'];
    $value = true;
    
    if (count($data['children']) >= 1) {
      $value = $this->visitChild($shell, $data['children'][0]);
    }
    
    return array(
      'key' => $key,
      'value' => $value,
    );
  }
  
}

    