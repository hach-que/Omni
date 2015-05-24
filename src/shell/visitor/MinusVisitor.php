<?php

final class MinusVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $a = $this->visitChild($shell, $data['children'][0]);
    $b = $this->visitChild($shell, $data['children'][1]);
    
    return $a - $b;
  }
  
}
