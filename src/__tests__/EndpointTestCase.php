<?php

abstract class EndpointTestCase extends PhutilTestCase {

  public function testNull() {
    $this->performSerialization(null);
  }
  
  public function testInteger() {
    $this->performSerialization(42);
  }
  
  public function testSmallFloat() {
    $this->performSerialization(42.0);
  }
  
  public function testBigFloat() {
    $this->performSerialization(424242424242.0);
  }
  
  public function testString() {
    $this->performSerialization("hello");
  }
  
  public function testStringWithNullBytes() {
    $this->performSerialization("h\0e\0l\0l\0o\0");
  }
  
  public function testGenericObjectTrue() {
    $this->performSerializationStringEqual((object)true);
  }
  
  public function testGenericObjectFalse() {
    $this->performSerializationStringEqual((object)false);
  }
  
  public function testArrayEmpty() {
    $this->performSerialization(array());
  }
  
  public function testArraySingle() {
    $this->performSerialization(array(true));
  }
  
  public function testArrayValues() {
    $this->performSerialization(array(1, 2, 3, 4));
  }
  
  public function testArrayKeys() {
    $this->performSerialization(array("a" => 1, "b" => 2, 123 => 3, 456 => 4));
  }

  protected function performSerialization($expected_value) {
    if ($this->supportsOnlyString() && !is_string($expected_value)) {
      $expected_value = print_r($expected_value, true);
    }
    
    if ($this->supportsOnlyString() && is_string($expected_value)) {
      $expected_value = str_replace("\n", "", $expected_value);
      $expected_value = str_replace("\0", "", $expected_value);
    }
    
    $endpoint = new Endpoint();
    $endpoint->setReadFormat($this->getSerializationFormat());
    $endpoint->setWriteFormat($this->getSerializationFormat());
    $endpoint->write($expected_value);
    $value = $endpoint->read();
    $endpoint->close();
    if ($value == $expected_value) {
      $this->assertEqual(0, 0);
    } else {
      $this->assertEqual($expected_value, $value);
    }
  }

  protected function performSerializationStringEqual($expected_value) {
    if ($this->supportsOnlyString() && !is_string($expected_value)) {
      $expected_value = print_r($expected_value, true);
    }
    
    if ($this->supportsOnlyString() && is_string($expected_value)) {
      $expected_value = str_replace("\n", "", $expected_value);
      $expected_value = str_replace("\0", "", $expected_value);
    }
    
    $endpoint = new Endpoint();
    $endpoint->setReadFormat($this->getSerializationFormat());
    $endpoint->setWriteFormat($this->getSerializationFormat());
    $endpoint->write($expected_value);
    $value = $endpoint->read();
    $endpoint->close();
    $this->assertEqual(print_r($expected_value, true), print_r($value, true));
  }
  
  protected abstract function getSerializationFormat();
  
  protected function supportsOnlyString() {
    return false;
  }
}
