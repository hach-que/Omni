<?php

final class InvocationVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $target = $this->visitChild($shell, $data['children'][0]);
    $arguments = $this->visitChild($shell, $data['children'][1])->getCopy();
    
    if ($target instanceof OmniFunction) {
      return $target->call($shell, $arguments);
    } else if ($target instanceof MethodCallReference) {
      return $target->call($arguments);
    } else if (is_callable($target)) {
      return call_user_func_array($target, $arguments);
    } else {
      throw new Exception(get_class($target).' is not callable!');
    }
  }
  
}

    