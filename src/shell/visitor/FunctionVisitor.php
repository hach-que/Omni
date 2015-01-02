<?php

final class FunctionVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    return new OmniFunction($data['children'][0]);
  }
  
}
