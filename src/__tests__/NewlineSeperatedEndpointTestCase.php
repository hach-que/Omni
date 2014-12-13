<?php

final class NewlineSeperatedEndpointTestCase extends EndpointTestCase {

  protected function getSerializationFormat() {
    return Endpoint::FORMAT_NEWLINE_SEPARATED;
  }

  protected function supportsOnlyString() {
    return true;
  }

}