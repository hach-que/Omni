<?php

final class OmniFunction extends Phobject {

  private $statementData;

  public function __construct(array $statement_data) {
    $this->statementData = $statement_data;
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

}