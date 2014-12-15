<?php

final class NumberVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    return $data['data'];
  }
  
}

    