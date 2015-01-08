<?php

final class VariableVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (!$this->getAllowSideEffects()) {
      try {
        return $shell->getVariable($data['data']);
      } catch (Exception $ex) {
        return null;
      }
    }
    
    return $shell->getVariable($data['data']);
  }
  
}

    