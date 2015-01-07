<?php

final class PHPVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (!$this->getAllowSideEffects()) {
      throw new EvaluationWouldCauseSideEffectException();
    }
  
    $script = $data['data'];
    $script = trim($script);
    $script = substr($script, strlen("<?php"));
    $script = substr($script, 0, strlen($script) - strlen("?>"));
    
    // just use eval lol
    return eval($script);
  }
  
}

    