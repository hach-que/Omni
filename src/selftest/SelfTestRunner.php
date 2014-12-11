<?php

final class SelfTestRunner {

  public function run() {
    $tests = id(new PhutilSymbolLoader())
      ->setAncestorClass('Tests')
      ->loadObjects();
      
    foreach ($tests as $test) {
      $test->runTests();
    }
  }

}