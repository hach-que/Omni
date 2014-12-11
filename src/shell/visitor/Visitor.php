<?php

abstract class Visitor {
  
  abstract function visit(Shell $shell, array $data);
  
  protected function visitChild(Shell $shell, array $child) {
    $mappings = array(
      'arguments' => 'ArgumentsVisitor',
      'assignment' => 'AssignmentVisitor',
      'command' => 'CommandVisitor',
      'double_quoted' => 'DoubleQuotedVisitor',
      'fragments' => 'FragmentsVisitor',
      'fragment' => 'FragmentVisitor',
      'pipeline' => 'PipelineVisitor',
      'root' => 'RootVisitor',
      'single_quoted' => 'SingleQuotedVisitor',
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