<?php

final class StatementsVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    try {
      foreach ($data['children'] as $child) {
        $this->visitChild($shell, $child);
      }
    } catch (ReturnFlowControlException $ex) {
      return $ex->getReturnValue();
    }
    
    return null;
  }
  
}
