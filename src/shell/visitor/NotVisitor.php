<?php

final class NotVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $value = $this->visitChild($shell, $data['children'][0]);
    
    return !$value;
  }
  
}
