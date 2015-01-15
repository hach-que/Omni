<?php

final class OmniFunction extends Phobject {

  private $statementData;
  private $original;
  private $implicitReturn;

  public function __construct(array $statement_data, $original, $implicit_return = false) {
    $this->statementData = $statement_data;
    $this->original = $original;
    $this->implicitReturn = $implicit_return;
  }
  
  public function getOriginal() {
    return $this->original;
  }
  
  public function call(Shell $shell, array $arguments) {
    $shell->beginVariableScope();

    $shell->setVariable('argc', count($arguments));
    for ($i = 0; $i < count($arguments); $i++) {
      $shell->setVariable($i + 1, $arguments[$i]);
    }
    
    list($visitor, $data) = $this->constructVisitor();
    
    $result = $visitor->visit($shell, $data);
    
    $shell->endVariableScope();
    
    return $result;
  }
  
  public function callIterator(Shell $shell, array $arguments, $iterator) {
    $shell->beginVariableScope();

    $shell->setVariable('argc', count($arguments));
    for ($i = 0; $i < count($arguments); $i++) {
      $shell->setVariable($i + 1, $arguments[$i]);
    }
    $shell->setVariable('_', $iterator);
    
    list($visitor, $data) = $this->constructVisitor();
    
    $result = $visitor->visit($shell, $data);
    
    $shell->endVariableScope();
    
    return $result;
  }
  
  private function constructVisitor() {
    $visitor = new StatementsVisitor();
      
    if ($this->implicitReturn) {
      $data = array(
        'type' => 'statements',
        'original' => $this->statementData['original'],
        'relative' => $this->statementData['relative'],
        'data' => null,
        'children' => array(
          array(
            'type' => 'return',
            'original' => $this->statementData['original'],
            'relative' => $this->statementData['relative'],
            'data' => null,
            'children' => array($this->statementData),
          )
        )
      );
    } else {
      $data = $this->statementData;
    }
    
    return array($visitor, $data);
  }

}