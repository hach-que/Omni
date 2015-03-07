<?php

final class AccessVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    $parent = $this->visitChild($shell, $data['children'][0]);
    $child = $this->visitChild($shell, $data['children'][1]);
    
    $is_assignment = false;
    if ($data['data'] === 'assign') {
      $is_assignment = true;
      $value = $this->visitChild($shell, $data['children'][2]);
    }
    
    if ($is_assignment) {
      if (!$this->getAllowSideEffects()) {
        throw new EvaluationWouldCauseSideEffectException();
      }
    }
    
    if (is_string($parent)) {
      // Wrap the string in a StringOperator so we
      // can call methods on it.
      $parent = new StringOperator($parent);
    }
    
    if ($parent instanceof ArrayContainer) {
      if ($is_assignment) {
        if ($child === '[]') {
          $parent->append($value);
        } else {
          $parent->set($child, $value);
        }
        return $value;
      } else {
        return $parent->get($child);
      }
    } else if (is_object($parent)) {
      $result = UserFriendlyFormatter::getObjectPropertiesAndMethods($parent);
      
      foreach ($result as $name => $info) {
        if ($name === $child || $name === $child.'()') {
          if ($info['php-type'] === 'method') {
            omni_trace('returning method call reference');
            return new MethodCallReference($parent, $info['php-name']);
          } else if ($info['php-type'] === 'method-property') {
            if ($is_assignment && !$info['writable']) {
              throw new Exception('Attempted to assign to \''.$name.'\', but it is a read-only property.');
            }
            
            $reflection = new ReflectionClass($parent);
            
            if ($is_assignment) {
              $method = $reflection->getMethod($info['php-write-name']);
              $method->invokeArgs($parent, array($value));
              return $value;
            } else {
              $method = $reflection->getMethod($info['php-name']);
              return $method->invokeArgs($parent, array());
            }
          } else if ($info['php-type'] === 'property') {
            if ($is_assignment && !$info['writable']) {
              throw new Exception('Attempted to assign to \''.$name.'\', but it is a read-only property.');
            }
            
            if ($info['writable']) {
              throw new Exception('Raw writable properties are not supported.');
            }
            
            $reflection = new ReflectionClass($parent);
            $property = $reflection->getProperty($info['php-name']);
            return $property->getValue($parent);
          } else {
            throw new Exception('Unknown PHP type for member '.$name);
          }
        }
      }
  
      if (!$this->getAllowSideEffects()) {
        return null;
      }
      
      throw new Exception(
        'Unable to find property '.$child.' on object.');
    } else {
      if (!$this->getAllowSideEffects()) {
        return null;
      }
      
      throw new Exception(
        'Unable to access members of '.
        $data['children'][0]['original'].
        '; it is not an array or object.');
    }
  }
  
}

    