<?php

final class ArrayElementVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    if (count($data['children']) === 1) {
      return array(
        'has-key' => false,
        'value' => $this->visitChild($shell, $data['children'][0]),
      );
    } else if (count($data['children']) === 2) {
      return array(
        'has-key' => false,
        'key' => $this->visitChild($shell, $data['children'][0]),
        'value' => $this->visitChild($shell, $data['children'][1]),
      );
    } else {
      throw new Exception('array_element constructed incorrected');
    }
  }
  
}

    