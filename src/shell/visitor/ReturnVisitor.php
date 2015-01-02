<?php

final class ReturnVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $value = $this->visitChild($shell, $data['children'][0]);
    
    throw new ReturnFlowControlException($value);
  }
  
}

    