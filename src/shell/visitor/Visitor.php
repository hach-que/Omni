<?php

abstract class Visitor {
  
  private $allowSideEffects = true;
  
  public function setAllowSideEffects($side_effects) {
    $this->allowSideEffects = $side_effects;
    return $this;
  }
  
  public function getAllowSideEffects() {
    return $this->allowSideEffects;
  }
  
  public function visit(Shell $shell, array $data) {
    omni_trace("begin visit ".get_class($this));
    
    $value = $this->visitImpl($shell, $data);
    
    omni_trace("end visit ".get_class($this));
    
    if (is_array($value)) {
      omni_trace("automatically wrapping raw array as ArrayContainer");
      $value = new ArrayContainer($value);
    }
    
    return $value;
  }
  
  protected abstract function visitImpl(Shell $shell, array $data);
  
  protected function visitChild(Shell $shell, array $child) {
    return $this->visitChildCall(
      $shell,
      $child,
      'visit');
  }
  
  protected function visitChildCall(Shell $shell, array $child, $func) {
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
      'function' => 'FunctionVisitor',
      'if' => 'IfVisitor',
      'invocation' => 'InvocationVisitor',
      'key_values' => 'KeyValuesVisitor',
      'key_value' => 'KeyValueVisitor',
      'number' => 'NumberVisitor',
      'php' => 'PHPVisitor',
      'pipeline' => 'PipelineVisitor',
      'return' => 'ReturnVisitor',
      'single_quoted' => 'SingleQuotedVisitor',
      'statements' => 'StatementsVisitor',
      'statement' => 'StatementVisitor',
      'variable' => 'VariableVisitor',
      'while' => 'WhileVisitor',
    );
  
    $visitor_name = idx($mappings, $child['type']);
    if ($visitor_name === null) {
      throw new Exception('No visitor mapping for "'.$child['type'].'"!');
    }
    
    $visitor = new $visitor_name();
    $visitor->setAllowSideEffects($this->getAllowSideEffects());
    return $visitor->$func($shell, $child);
  }
  
  public static function visitCustomChild(Shell $shell, array $child, $allow_side_effects) {
    $visitor = new StatementsVisitor();
    $visitor->setAllowSideEffects($allow_side_effects);
    return $visitor->visitChild($shell, $child);
  }
  
  public function isSafeToAppendFragment(Shell $shell, array $data) {
    return $this->isSafeToAppendFragmentImpl($shell, $data);
  }
  
  protected function isSafeToAppendFragmentImpl(Shell $shell, array $data) {
    return false;
  }
  
  protected function isSafeToAppendFragmentChild(Shell $shell, array $child) {
    return $this->visitChildCall(
      $shell,
      $child,
      'isSafeToAppendFragmentImpl');
  }
  
}