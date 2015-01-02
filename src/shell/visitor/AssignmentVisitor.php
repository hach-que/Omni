<?php

final class AssignmentVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $target = $data['children'][0]['data'];
    $value = $this->visitChild($shell, $data['children'][1]);
      
    $shell->setVariable($target, $value);
    
    return $value;
  }
  
}
