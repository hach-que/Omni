<?php

final class AssignmentVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    $target = $data['children'][0]['data'];
    $value = $this->visitChild($shell, $data['children'][1]);
    
    $shell->setVariable($target, $value);
    
    return null;
  }
  
}
