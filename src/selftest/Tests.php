<?php

abstract class Tests {

  abstract function runTests();

  protected function createPass($string) {
    echo 'PASS: '.str_replace("\n", "\\n", $string)."\n";
  }

  protected function createFail($string) {
    echo 'FAIL: '.str_replace("\n", "\\n", $string)."\n";
    exit(1);
  }
  
}