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
    
    if (is_array($parent)) {
      return $parent[$child];
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
              $method = $reflection->getMethod($info['php-name-write']);
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
  
      throw new Exception(
        'Unable to find property '.$child.' on object.');
    } else {
      throw new Exception(
        'Unable to access members of '.
        $data['children'][0]['original'].
        '; it is not an array or object.');
    }
  }
  
}

    