<?php

final class AddVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $a = $this->visitChild($shell, $data['children'][0]);
    $b = $this->visitChild($shell, $data['children'][1]);
    
    if (is_string($a) && is_string($b)) {
      return $a.$b;
    } else {
      return $a + $b;
    }
  }
  
}
