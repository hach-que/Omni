<?php

final class FragmentsVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (count($data['children']) === 1) {
      // Preserve the data type of the child if there is only a
      // single child.
      return $this->visitChild($shell, $data['children'][0]);
    }
    
    // Otherwise run through the fragments with concatenation.
    $result = '';
    foreach ($data['children'] as $child) {
      $result .= $this->visitChild($shell, $child);
    }
    return $result;
  }
  
  public function isSafeToAppendFragmentImpl(Shell $shell, array $data) {
    foreach ($data['children'] as $child) {
      if (!$this->isSafeToAppendFragmentChild($shell, $child)) {
        return false;
      }
    }
    return true;
  }
  
}

    