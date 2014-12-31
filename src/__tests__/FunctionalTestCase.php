<?php

final class FunctionalTestCase extends PhutilTestCase {
  
  public function testArgumentsString1() {
    $this->runFunctionalTest('arguments-string-1', array('asdf'));
  }
  
  public function testArgumentsNumeric1() {
    $this->runFunctionalTest('arguments-numeric-1', array('123'));
  }

  public function testNestedScriptsBackground() {
    $this->runFunctionalTest('nested-scripts-background');
  }
  
  public function testNestedScriptsForeground() {
    $this->runFunctionalTest('nested-scripts-foreground');
  }
  
  public function testNewBuiltin1() {
    $this->runFunctionalTest('new-builtin-1');
  }
  
  private function runFunctionalTest($name, $args = null) {
    $omni = phutil_get_library_root('omni').'/../bin/omni';
    $cwd = phutil_get_library_root('omni').'/../test/'.$name;
  
    if ($args === null) {
      $args = array();
    }
    
    $expect = file_get_contents(
      phutil_get_library_root('omni').'/../test/'.$name.'/expected');
  
    list($err, $stdout, $stderr) = id(new ExecFuture('%C ./run.sh %Ls', $omni, $args))
      ->setCWD($cwd)
      ->resolve();
    $this->assertEqual(0, $err);
    $this->assertEqual($stdout, $expect);
  }
  
}