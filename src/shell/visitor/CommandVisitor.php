<?php

final class CommandVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $arguments = $this->visitChild($shell, $data['children'][0]);
    
    if ($arguments->has(0)) {
      if (!is_string($arguments->get(0))) {
        throw new Exception('Executable name must be a string, did you mean \'echo '.$data['original'].'\'?');
      }
    }
    
    return new Process($arguments->deepCopy(), $data['children'][0]['original']);
  }
  
}

    