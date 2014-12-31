<?php

final class IfVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $expression = $this->visitChild($shell, $data['children'][0]);
    
    if ($expression) {
      return $this->visitChild($shell, $data['children'][1]);
    } else {
      if (count($data['children']) >= 3) {
        return $this->visitChild($shell, $data['children'][2]);
      }
    }
    
    return null;
  }
  
}
