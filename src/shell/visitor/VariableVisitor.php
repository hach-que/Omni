<?php

final class VariableVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    return $shell->getVariable($data['data']);
  }
  
}

    