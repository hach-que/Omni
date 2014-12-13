<?php

final class NullSeperatedEndpointTestCase extends EndpointTestCase {

  protected function getSerializationFormat() {
    return Endpoint::FORMAT_NULL_SEPARATED;
  }

  protected function supportsOnlyString() {
    return true;
  }

}