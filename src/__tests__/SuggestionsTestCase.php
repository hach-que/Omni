<?php

final class SuggestionsTestCase extends PhutilTestCase {
  
  public function testLocateAt0() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 0,
      'type' => 'fragment',
      'value' => 'git',
    ));
  }
  
  public function testLocateAt1() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 1,
      'type' => 'fragment',
      'value' => 'git',
    ));
  }
  
  public function testLocateAt2() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 2,
      'type' => 'fragment',
      'value' => 'git',
    ));
  }
  
  public function testLocateAt3() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 3,
      'type' => 'fragment',
      'value' => 'git',
    ));
  }
  
  public function testLocateAt4() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 4,
      'type' => 'fragment',
      'value' => '--work-tree=abc',
    ));
  }
  
  public function testLocateAt4WithExtraSpacing() {
    $this->runSuggestionTest(array(
      'text' => 'git   --work-tree=abc checkout phutil-bug-fix',
      'position' => 4,
      'type' => 'arguments',
      'value' => array(
        'git',
        '--work-tree=abc',
        'checkout',
        'phutil-bug-fix',
      ),
    ));
  }
  
  public function testLocateAt18() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 18,
      'type' => 'fragment',
      'value' => '--work-tree=abc',
    ));
  }
  
  public function testLocateAt20() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 20,
      'type' => 'fragment',
      'value' => 'checkout',
    ));
  }
  
  public function testLocateAt27() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 27,
      'type' => 'fragment',
      'value' => 'checkout',
    ));
  }
  
  public function testLocateAt29() {
    $this->runSuggestionTest(array(
      'text' => 'git --work-tree=abc checkout phutil-bug-fix',
      'position' => 29,
      'type' => 'fragment',
      'value' => 'phutil-bug-fix',
    ));
  }
  
  private function runSuggestionTest($e) {
    $shell = new Shell();
    $result = omnilang_parse($e['text']);
    $this->assertTrue((!(!$result)), $e['text'].': '.omnilang_get_error());
    
    $engine = new SuggestionEngine();
    $node = $engine->traverseToPosition($result, $e['position']);
    $this->assertEqual($e['type'], idx(last($node), 'type'), $e['text']);
    
    $value = Visitor::visitCustomChild($shell, last($node), false);
    if ($value instanceof ArrayContainer) {
      $value = $value->getCopy();
    }
    $this->assertEqual($e['value'], $value, print_r($e, true));
  }
  
}