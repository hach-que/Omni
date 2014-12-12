<?php

final class TypeConverter extends Phobject {

  const TYPE_BOOLEAN = 'boolean';
  const TYPE_INTEGER = 'integer';
  const TYPE_FLOAT = 'float';
  const TYPE_STRING = 'string';
  const TYPE_ARRAY = 'array';
  const TYPE_OBJECT = 'object';
  const TYPE_RESOURCE = 'resource';
  const TYPE_NULL = 'NULL';
  const TYPE_JSON = 'JsonContainer';
  const TYPE_RAW_BYTES = 'PhutilRope';

  public function convert($value, $type, $permit_nulls = true) {
    switch (gettype($value)) {
      case 'boolean':
        return $this->performGenericConvert($value, $type, 'boolean');
      case 'integer':
        return $this->performGenericConvert($value, $type, 'integer');
      case 'double':
      case 'float':
        return $this->performGenericConvert($value, $type, 'float');
      case 'string':
        return $this->performGenericConvert($value, $type, 'string');
      case 'array':
        return $this->performGenericConvert($value, $type, 'array');
      case 'object':
        return $this->convertObjectTo($value, $type);
      case 'resource':
        if ($type === 'resource') {
          return $value;
        } else {
          throw new Exception('Unable to convert '.$target_name.' to resource');
        }
      case 'NULL':
        if ($permit_nulls) {
          return null;
        } else {
          throw new Exception('Unable to convert null value to '.$type);
        }
      default:
        if (class_exists($type)) {
          return $this->convertObjectTo($value, $type);
        } else {
          throw new Exception('Unable to convert '.$target_name.' to unknown type');
        }
    }
  }
  
  private function performGenericConvert($value, $type, $target_name) {
    switch ($type) {
      case 'boolean':
        return (bool)$value;
      case 'integer':
        return (int)$value;
      case 'double':
      case 'float':
        return (float)$value;
      case 'string':
        return (string)$value;
      case 'array':
        return (array)$value;
      case 'object':
        return (object)$value;
      case 'NULL':
        return null;
      default:
        throw new Exception('Unable to convert '.$target_name.' to '.$type);
    }
  }
  
  private function convertObjectTo($value, $type) {
    // Attempt to convert the object to the specified type.
    switch ($type) {
      case 'boolean':
        if (method_exists($value, 'castToBoolean')) {
          return $value->castToBoolean();
        }
        break;
      case 'integer':
        if (method_exists($value, 'castToInteger')) {
          return $value->castToInteger();
        }
        break;
      case 'double':
      case 'float':
        if (method_exists($value, 'castToFloat')) {
          return $value->castToFloat();
        }
        break;
      case 'string':
        if (method_exists($value, 'castToString')) {
          return $value->castToString();
        }
        
        // The only official casting semantic offered by PHP.
        if (method_exists($value, '__toString')) {
          return $value->__toString();
        }
        break;
      case 'array':
        if (method_exists($value, 'castToArray')) {
          return $value->castToArray();
        }
        break;
      case 'object':
        return $value;
      case 'resource':
        throw new Exception('Unable to convert object to resource');
      case 'NULL':
        return null;
      default:
        if (class_exists($type)) {
          if (method_exists($value, 'castTo'.$type)) {
            return call_user_func(array($value, 'castTo'.$type));
          } else {
            if (method_exists($value, 'castTo')) {
              $result = call_user_func(array($value, 'castTo'));
              if ($result !== null) {
                return $result;
              }
            }
          }
        }
        
        throw new Exception('Unable to convert '.$target_name.' to '.$type);
    }
  }
  
}