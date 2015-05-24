<?php

final class GreaterThanVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $left = $this->visitChild($shell, $data['children'][0]);
    $right = $this->visitChild($shell, $data['children'][1]);
    
    return $left > $right;
  }
  
}
