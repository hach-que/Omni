<?php

abstract class Visitor {
  
  public function visit(Shell $shell, array $data) {
    omni_trace("begin visit ".get_class($this));
    
    $value = $this->visitImpl($shell, $data);
    
    omni_trace("end visit ".get_class($this));
    
    return $value;
  }
  
  protected abstract function visitImpl(Shell $shell, array $data);
  
  protected function visitChild(Shell $shell, array $child) {
    $mappings = array(
      'access' => 'AccessVisitor',
      'arguments' => 'ArgumentsVisitor',
      'array_decl' => 'ArrayDeclVisitor',
      'array_def' => 'ArrayDefVisitor',
      'array_element' => 'ArrayElementVisitor',
      'assignment' => 'AssignmentVisitor',
      'command' => 'CommandVisitor',
      'double_quoted' => 'DoubleQuotedVisitor',
      'expression' => 'ExpressionVisitor',
      'foreach' => 'ForeachVisitor',
      'fragments' => 'FragmentsVisitor',
      'fragment' => 'FragmentVisitor',
      'if' => 'IfVisitor',
      'invocation' => 'InvocationVisitor',
      'key_values' => 'KeyValuesVisitor',
      'key_value' => 'KeyValueVisitor',
      'number' => 'NumberVisitor',
      'php' => 'PHPVisitor',
      'pipeline' => 'PipelineVisitor',
      'single_quoted' => 'SingleQuotedVisitor',
      'statements' => 'StatementsVisitor',
      'statement' => 'StatementVisitor',
      'variable' => 'VariableVisitor',
    );
  
    $visitor_name = idx($mappings, $child['type']);
    if ($visitor_name === null) {
      throw new Exception('No visitor mapping for "'.$child['type'].'"!');
    }
    
    $visitor = new $visitor_name();
    return $visitor->visit($shell, $child);
  }
  
}