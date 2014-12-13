<?php

final class ByteStreamEndpointTestCase extends EndpointTestCase {

  protected function getSerializationFormat() {
    return Endpoint::FORMAT_BYTE_STREAM;
  }

  protected function supportsOnlyString() {
    return true;
  }
  
}