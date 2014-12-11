<?php

final class FragmentVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    return $data['data'];
  }
  
}

    