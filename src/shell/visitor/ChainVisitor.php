<?php

final class ChainVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if ($data['children'][0]['type'] === 'pipeline') {
      return $this->visitChild($shell, $data['children'][0]);
    }
    
    omni_trace("constructing chain");
    
    return new Chain(
      $this->visitChild($shell, $data['children'][0]),
      $data['data'],
      $this->visitChild($shell, $data['children'][1]));
  }
  
}
