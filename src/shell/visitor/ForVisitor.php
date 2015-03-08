<?php

final class ForVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (!$this->getAllowSideEffects()) {
      throw new EvaluationWouldCauseSideEffectException();
    }
  
    $start = $this->visitChild($shell, $data['children'][0]);
    $end = $this->visitChild($shell, $data['children'][1]);
    $step = $this->visitChild($shell, $data['children'][2]);
    
    $mapping_count = count($data['children'][3]['children']);
    if ($mapping_count === 1) {
      $key_target = null;
      $value_target = $this->visitChild($shell, $data['children'][3]['children'][0]);
    } else if ($mapping_count === 2) {
      throw new Exception('for mapping does not support $k => $v format');
    } else {
      throw new Exception('for mapping not structured correctly');
    }
    
    for ($i = $start; $i < $end; $i += $step) {
      $shell->setVariable($value_target, $i);
      $this->visitChild($shell, $data['children'][4]);
    }
    
    return null;
  }
  
}
