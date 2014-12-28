<?php

final class CommandVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $arguments = $this->visitChild($shell, $data['children'][0]);
    return new Process($arguments, $data['children'][0]['original']);
  }
  
}

    