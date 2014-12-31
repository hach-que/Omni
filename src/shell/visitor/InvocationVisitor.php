<?php

final class InvocationVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $target = $this->visitChild($shell, $data['children'][0]);
    $arguments = $this->visitChild($shell, $data['children'][1]);
    
    if (is_callable($target)) {
      return call_user_func_array($target, $arguments);
    }
  }
  
}

    