<?php

final class ForeachVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (!$this->getAllowSideEffects()) {
      throw new EvaluationWouldCauseSideEffectException();
    }
  
    $expression = $this->visitChild($shell, $data['children'][0]);
    if ($expression instanceof ArrayContainer) {
      $expression = $expression->getCopy();
    }
    
    $mapping_count = count($data['children'][1]['children']);
    if ($mapping_count === 1) {
      $key_target = null;
      $value_target = $this->visitChild($shell, $data['children'][1]['children'][0]);
    } else if ($mapping_count === 2) {
      $key_target = $this->visitChild($shell, $data['children'][1]['children'][0]);
      $value_target = $this->visitChild($shell, $data['children'][1]['children'][1]);
    } else {
      throw new Exception('foreach mapping not structured correctly');
    }
    
    foreach ($expression as $key => $value) {
      if ($key_target !== null) {
        $shell->setVariable($key_target, $key);
      }
      
      $shell->setVariable($value_target, $value);
      
      $this->visitChild($shell, $data['children'][2]);
    }
    
    return null;
  }
  
}
