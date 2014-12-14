<?php

final class SingleQuotedVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    return $data['data'];
  }
  
}

    