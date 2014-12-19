<?php

final class PipeControllerTestCase extends PhutilTestCase {

  public function testPHPSerializedArray() {
    $pipe = new Pipe();
    $write = $pipe->createInboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION);
    $read = $pipe->createOutboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION);
  
    $write->write(array("success" => true));
    $write->close();
    
    $result = $read->read();
    $this->assertEqual(true, idx($result, "success"));
  }
  
  public function testOrderIsCorrect() {
    $pipe = new Pipe();
    $write = $pipe->createInboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION);
    $read = $pipe->createOutboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION);
    
    $write->write(array("order_1" => true));
    $write->write(array("order_2" => true));
    $write->close();
    
    $this->assertEqual(true, idx($read->read(), "order_1"));
    $this->assertEqual(true, idx($read->read(), "order_2"));
  }
  
  public function testMultipleInboundEndpoints() {
    omni_trace("TEST: CREATING RUNTIME PIPE");
    $pipe = new Pipe();
    omni_trace("TEST: CREATING WRITE ENDPOINT 1");
    $write_1 = $pipe->createInboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION);
    omni_trace("TEST: CREATING WRITE ENDPOINT 2");
    $write_2 = $pipe->createInboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION);
    omni_trace("TEST: CREATING READ ENDPOINT");
    $read = $pipe->createOutboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION);
    
    omni_trace("TEST: WRITING DATA");
    $write_2->write(array("success" => true));
    omni_trace("TEST: CLOSING WRITE ENDPOINT 2");
    $write_2->close();
    
    omni_trace("TEST: READING FROM ENDPOINT");
    $result = $read->read();
    omni_trace("TEST: PERFORMING ASSERT");
    $this->assertEqual(true, idx($result, "success"));
  }
  
  // NOTE: Split distribution is impossible to reliably test because it is
  // indeterminate as to what outbound endpoints will get what objects.
  
  public function testMultipleOutboundEndpointsRoundRobinDistribution() {
    $pipe = new Pipe();
    $write = $pipe->createInboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION, "write");
    $read_1 = $pipe->createOutboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION, "read_1");
    $read_2 = $pipe->createOutboundEndpoint(Endpoint::FORMAT_PHP_SERIALIZATION, "read_2");
    
    $write->write(array("entry_1" => true));
    $write->write(array("entry_2" => true));
    $write->close();
    
    $result_1 = $read_1->read();
    $result_2 = $read_2->read();
    $this->assertEqual(true, idx($result_1, "entry_1"));
    $this->assertEqual(true, idx($result_2, "entry_2"));
  }
  
  public function testByteStreamData() {
    $pipe = new Pipe();
    $write = $pipe->createInboundEndpoint(Endpoint::FORMAT_BYTE_STREAM);
    $read = $pipe->createOutboundEndpoint(Endpoint::FORMAT_BYTE_STREAM);
    
    $write->write('content');
    $write->close();
    
    $result = '';
    while ($result !== 'content') {
      $result .= $read->read();
    }
    
    $this->assertEqual('content', $result);
  }
  
  public function testHugeByteStreamData() {
    $pipe = new Pipe();
    $write = $pipe->createInboundEndpoint(Endpoint::FORMAT_BYTE_STREAM);
    $read = $pipe->createOutboundEndpoint(Endpoint::FORMAT_BYTE_STREAM);
  
    $data = '';
    for ($i = 0; $i < 512; $i++) {
      $data .= '0123456789';
    }
    
    $write->write($data);
    $write->close();
    
    $result = '';
    while (strlen($result) < 5120) {
      $result .= $read->read();
    }
    
    $this->assertEqual($data, $result);
  }
  
}