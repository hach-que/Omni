<?php

final class ArgumentsVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    $arguments = array();
    foreach ($data['children'] as $child) {
      $arguments[] = $this->visitChild($shell, $child);
    }
    return $arguments;
  }
  
}

    