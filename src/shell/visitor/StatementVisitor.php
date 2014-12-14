<?php

final class StatementVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    foreach ($data['children'] as $child) {
      $this->visitChild($shell, $child);
    }
    
    return null;
  }
  
}
