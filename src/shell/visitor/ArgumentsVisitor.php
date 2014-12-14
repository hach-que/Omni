<?php

final class ArgumentsVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $arguments = array();
    foreach ($data['children'] as $child) {
      $arguments[] = $this->visitChild($shell, $child);
    }
    return $arguments;
  }
  
}

    