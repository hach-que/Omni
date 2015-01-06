<?php

final class OmniFunction extends Phobject {

  private $statementData;
  private $original;

  public function __construct(array $statement_data, $original) {
    $this->statementData = $statement_data;
    $this->original = $original;
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
    
    $result = id(new StatementsVisitor())->visit($shell, $this->statementData);
    
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
    
    $result = id(new StatementsVisitor())->visit($shell, $this->statementData);
    
    $shell->endVariableScope();
    
    return $result;
  }

}