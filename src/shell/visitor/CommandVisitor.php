<?php

final class CommandVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    $arguments = $this->visitChild($shell, $data['children'][0]);
    return new Process($arguments);
  }
  
}

    