<?php

final class FragmentVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    return $data['data'];
  }
  
  public function isSafeToAppendFragmentImpl(Shell $shell, array $data) {
    return true;
  }
  
}

    