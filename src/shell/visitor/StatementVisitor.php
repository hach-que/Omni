<?php

final class StatementVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    foreach ($data['children'] as $child) {
      $this->visitChild($shell, $child);
    }
    
    return null;
  }
  
}
