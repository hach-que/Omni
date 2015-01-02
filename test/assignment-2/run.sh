#!/bin/omni

: <?php

final class AssignmentTest {

  private $test;

  public function getTest() {
    return $this->test;
  }

  public function setTest($value) {
    $this->test = $value;
  }

}

?>

: $test = $(new -t AssignmentTest)
: $test->test = "hello"
echo ($test->test)

