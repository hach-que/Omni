<?php

final class InvocationVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (!$this->getAllowSideEffects()) {
      throw new EvaluationWouldCauseSideEffectException();
    }
  
    $target = $this->visitChild($shell, $data['children'][0]);
    $arguments = $this->visitChild($shell, $data['children'][1])->getCopy();
    
    return $shell->invokeCallable($target, $arguments);
  }
  
}

    