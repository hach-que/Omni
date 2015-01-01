<?php

final class ArrayDeclVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    return $this->visitChild($shell, $data['children'][0]);
  }
  
}

    