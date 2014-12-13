<?php

final class PHPSerializationEndpointTestCase extends EndpointTestCase {

  protected function getSerializationFormat() {
    return Endpoint::FORMAT_PHP_SERIALIZATION;
  }

}