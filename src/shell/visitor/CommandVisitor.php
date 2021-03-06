<?php

final class CommandVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (!$this->getAllowSideEffects()) {
      throw new EvaluationWouldCauseSideEffectException();
    }
  
    $arguments = $this->visitChild($shell, $data['children'][0]);
    
    if ($arguments->has(0)) {
      if (!is_string($arguments->get(0))) {
        throw new Exception('Executable name must be a string, did you mean \'echo '.$data['original'].'\'?');
      }
    }
    
    $expander = new ExpressionExpander();
    $arguments_copy = $arguments->deepCopy();
    $arguments_expanded = array();
    foreach ($arguments_copy as $arg) {
      $arguments_expanded[] = $expander->expandFilePath($arg);
    }
    
    return new Process($arguments_expanded, $data['children'][0]['original']);
  }
  
}

    