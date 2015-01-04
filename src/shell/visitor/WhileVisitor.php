<?php

final class WhileVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    while ($this->visitChild($shell, $data['children'][0])) {
      $this->visitChild($shell, $data['children'][1]);
    }
    
    return null;
  }
  
}
