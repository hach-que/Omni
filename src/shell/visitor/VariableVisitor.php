<?php

final class VariableVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    return $shell->getVariable($data['data']);
  }
  
}

    