<?php

final class ArrayDeclVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (count($data['children']) > 0) {
      return $this->visitChild($shell, $data['children'][0]);
    } else {
      return new ArrayContainer();
    }
  }
  
}

    