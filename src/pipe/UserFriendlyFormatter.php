<?php

/**
 * This class accepts objects and converts them to a user-friendly text
 * representation.
 *
 * This formatter maintains state between write requests, so that it can
 * format multiple arrays or objects in a row as a table.
 */
final class UserFriendlyFormatter extends Phobject {

  public function get($object) {
    try {
      assert_stringlike($object);
      if (is_bool($object)) {
        if ($object) {
          return 'true';
        } else {
          return 'false';
        } 
      } else {
        return (string)$object;
      }
    } catch (Exception $e) {
      if (is_array($object)) {
        return $this->getArray($object);
      } else if (is_resource($object)) {
        return $this->getResource($object);
      } else if (is_object($object)) {
        return $this->getObject($object);
      } else {
        return "Unknown object received for output (has type ".gettype($object).")";
      }
    }
  }
  
  private function getResource($resource) {
    return get_resource_type($resource)."\n";
  }
  
  private function getArray($array) {
    if ($this->arrayHasAllNumericKeys($array)) {
      $content = '';
      foreach ($array as $k => $v) {
        $content .= $this->get($v)."\n";
      }
      return $content;
    }
    
    if (count($array) > 10) {
      // When the array has a large number of keys, we print it
      // as a list (so that we don't render massive tables).
      $content = '';
      foreach ($array as $k => $v) {
        $content .= $this->get($k)." = ".$this->get($v)."\n";
      }
      return $content;
    }
    
    return $this->getFields($array);
  }
  
  private function arrayHasAllNumericKeys(array $array) {
    for ($i = 0; $i < count($array); $i++) {
      if (!array_key_exists($i, $array)) {
        return false;
      }
    }
    
    return true;
  }
  
  private function writeObject($object) {
    $reflection = new ReflectionClass($object);
    $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    
    $array = array();
    foreach ($properties as $property) {
      $array[$property->getName()] = $property->getValue();
    }
    foreach ($methods as $method) {
      $name = $method->getName();
      if (preg_match('/^get[A-Z]/', $name)) {
        if ($method->isStatic()) {
          continue;
        }
        if ($method->getNumberOfRequiredParameters() !== 0) {
          continue;
        }
        $field_name = substr($name, 3);
        $array[$field_name] = $method->invoke($object);
      }
    }
    
    return $this->getFields($array);
  }
  
  private function getFields($fields) {
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
      
      $content .= $key_string.': '.$value."\n";
    }
  
    $content .= "\n";
    return $content;
  }
  
}