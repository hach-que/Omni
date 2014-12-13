<?php

final class JsonLengthPrefixedEndpointTestCase extends EndpointTestCase {

  protected function getSerializationFormat() {
    return Endpoint::FORMAT_LENGTH_PREFIXED_JSON;
  }

  /**
   * Arrays with keys get turned into standard objects, so just make
   * sure the usual array index access works.
   */
  public function testArrayKeys() {
    $this->assertSkipped('JSON decoding converts arrays with keys to generic objects');
  }
  
}