<?php

/**
 * This class accepts objects and converts them to a user-friendly text
 * representation.
 *
 * This formatter maintains state between write requests, so that it can
 * format multiple arrays or objects in a row as a table.
 */
final class UserFriendlyFormatter extends Phobject {

  public function get($object, $prefix = '', $no_newline = false) {
    $newline = $no_newline ? '' : "\n";
    
    if ($object instanceof Exception) {
      return $this->getException($object, $prefix);
    }
    
    try {
      assert_stringlike($object);
      if (is_bool($object)) {
        if ($object) {
          return $this->indent('true', $prefix).$newline;
        } else {
          return $this->indent('false', $prefix).$newline;
        } 
      } else {
        return $this->indent((string)$object, $prefix).$newline;
      }
    } catch (Exception $e) {
      if (is_array($object)) {
        return $this->getArray($object, $prefix);
      } else if (is_resource($object)) {
        return $this->getResource($object, $prefix);
      } else if (is_object($object)) {
        return $this->getObject($object, $prefix);
      } else {
        return "Unknown object received for output (has type ".gettype($object).")";
      }
    }
  }
  
  private function getException(Exception $ex, $prefix) {
    if ($ex instanceof ProcessAwareException) {
      $process_trace = implode("\n", $ex->getProcessTrace());
      $ex = $ex->getOriginal();
    } else {
      $process_trace = '<originates in current process '.posix_getpid().'>';
    }
    
    return $this->indent(
      "\x1B[1m\x1B[31m".
      (string)$ex.
      "\nProcess trace:\n$process_trace\n\x1B[0m",
      $prefix);
  }
  
  private function getResource($resource, $prefix) {
    return $this->indent(get_resource_type($resource)."\n", $prefix);
  }
  
  private function getArray($array, $prefix) {
    if ($this->arrayHasAllNumericKeys($array)) {
      $content = '';
      foreach ($array as $k => $v) {
        $content .= $this->get($v, $this->incPrefix($prefix), true)."\n";
      }
      return $this->indent($content, $prefix);
    }
    
    if (count($array) > 10) {
      // When the array has a large number of keys, we print it
      // as a list (so that we don't render massive tables).
      $content = '';
      foreach ($array as $k => $v) {
        $content .= 
          $this->get($k, $this->incPrefix($prefix), true).
          " = ".
          $this->get($v, $this->incPrefix($prefix), true)."\n";
      }
      return $this->indent($content, $prefix);
    }
    
    return $this->getFields($array, $prefix);
  }
  
  private function arrayHasAllNumericKeys(array $array) {
    for ($i = 0; $i < count($array); $i++) {
      if (!array_key_exists($i, $array)) {
        return false;
      }
    }
    
    return true;
  }
  
  public static function getObjectPropertiesAndMethods($object) {
    $reflection = new ReflectionClass($object);
    $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $methods = mpull($methods, null, 'getName');
    
    $array = array();
    foreach ($properties as $property) {
      $array[$property->getName()] = array(
        'writable' => false,
        'value' => $property->getValue(),
        'php-type' => 'property',
        'php-name' => $property->getName(),
      );
    }
    foreach ($methods as $name => $method) {
      $show_method = false;
      
      if (preg_match('/^get[A-Z]/', $name)) {
        if ($method->isStatic()) {
          continue;
        }
        if ($method->getNumberOfRequiredParameters() !== 0) {
          continue;
        }
        $field_name = substr($name, 3);
        $upper_field_name = strtoupper($field_name[0]).substr($field_name, 1);
        $field_name = strtolower($field_name[0]).substr($field_name, 1);
        $array[$field_name] = array(
          'writable' => idx($methods, 'set'.$upper_field_name) !== null,
          'value' => $method->invoke($object),
          'php-type' => 'method-property',
          'php-name' => $name,
          'php-write-name' => 'set'.$upper_field_name,
        );
      } else if (preg_match('/^is[A-Z]/', $name)) {
        if ($method->isStatic()) {
          continue;
        }
        if ($method->getNumberOfRequiredParameters() !== 0) {
          continue;
        }
        $field_name = 'is'.substr($name, 2);
        $array[$field_name] = array(
          'writable' => idx($methods, 'set'.substr($name, 2)),
          'value' => $method->invoke($object),
          'php-type' => 'method-property',
          'php-name' => $name,
        );
      } else if (preg_match('/^set[A-Z]/', $name)) {
        $field_name = substr($name, 3);
        $upper_field_name = strtoupper($field_name[0]).substr($field_name, 1);
        if (idx($methods, 'get'.$upper_field_name) !== null) {
          // Represented as a property.
          continue;
        } else {
          $show_method = true;
        }
      } else if (preg_match('/^((__[a-z]+)|current|key|next|rewind|valid)$/', $name)) {
        // Ignore these methods.
      } else {
        $show_method = true;
      }
      
      if ($show_method) {
        $field_name = $name.'()';
        $parameter_strs = array();
        foreach ($method->getParameters() as $parameter) {
          $parameter_str = '$'.$parameter->getName();
          if ($parameter->isOptional()) {
            $parameter_str .= ' = ...';
          }
          if ($parameter->isPassedByReference()) {
            $parameter_str = '&'.$parameter_str;
          }
          if ($parameter->isArray()) {
            $parameter_str = 'array '.$parameter_str;
          }
          if ($parameter->isVariadic()) {
            $parameter_str = '...';
          }
          if ($parameter->getClass()) {
            $parameter_str = $parameter->getClass()->getName().' '.$parameter_str;
          }
          $parameter_strs[] = $parameter_str;
        }
        $array[$field_name] = array(
          'writable' => false,
          'value' => '('.implode(', ', $parameter_strs).')',
          'php-type' => 'method',
          'php-name' => $name,
        );
      }
    }
    
    return $array;
  }
  
  private function getObject($object, $prefix) {
    $array = self::getObjectPropertiesAndMethods($object);
    
    return $this->getFields($array, $prefix, true);
  }
  
  private function getFields($fields, $prefix, $values_have_writable_flag = false) {
    $max_key_length = 0;
    foreach ($fields as $key => $value) {
      $key_string = (string)$key;
      if (strlen($key_string) > $max_key_length) {
        $max_key_length = strlen($key_string);
      }
    }
    
    $max_key_length += ($max_key_length % 5);
  
    $content = "\n";
    
    foreach ($fields as $key => $value) {
      $key_string = (string)$key;
      $key_string = str_pad($key_string, $max_key_length, ' ');
      
      if ($values_have_writable_flag) {
        $w = '   ';
        if ($value['writable']) {
          $w = ' w ';
        }
        $content .= $key_string.$w.': '.$this->get($value['value'], $this->incPrefix($prefix), true)."\n";
      } else {
        $content .= $key_string.': '.$this->get($value, $this->incPrefix($prefix), true)."\n";
      }
    }
  
    if ($prefix === '') {
      $content .= "\n";
    }
    
    return $this->indent($content, $prefix);
  }
  
  private function indent($string, $prefix) {
    $parts = explode("\n", $string);
    for ($i = 0; $i < count($parts); $i++) {
      if ($i !== 0) {
        if ($i < count($parts) - 1 || $parts[$i] !== '') {
          $parts[$i] = $prefix.$parts[$i];
        }
      }
    }
    return implode("\n", $parts);
  }
  
  private function incPrefix($prefix) {
    return '  | '.$prefix;
  }
  
}