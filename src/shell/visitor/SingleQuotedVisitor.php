<?php

final class SingleQuotedVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    return $data['data'];
  }
  
}

    