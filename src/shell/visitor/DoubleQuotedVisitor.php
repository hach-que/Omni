<?php

final class DoubleQuotedVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    return $data['data'];
  }
  
}

    