<?php

final class DoubleQuotedVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    return $data['data'];
  }
  
}

    