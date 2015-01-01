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
  
  public function testIfStatement1() {
    $this->runFunctionalTest('if-statement-1');
  }
  
  public function testIfStatement1Multiline() {
    $this->runFunctionalTest('if-statement-1-multiline');
  }
  
  public function testIfStatement2() {
    $this->runFunctionalTest('if-statement-2');
  }
  
  public function testIfStatement2Multiline() {
    $this->runFunctionalTest('if-statement-2-multiline');
  }
  
  public function testCaptureExitCode() {
    $this->runFunctionalTest('capture-exit-code');
  }
  
  public function testLsExitCode0() {
    $this->runFunctionalTest('ls-exit-code-0');
  }
  
  public function testLsExitCode1() {
    $this->runFunctionalTest('ls-exit-code-1');
  }
  
  public function testExitCodeVar0() {
    $this->runFunctionalTest('exit-code-var-0');
  }
  
  public function testExitCodeVar1() {
    $this->runFunctionalTest('exit-code-var-1');
  }
  
  public function testForeachStatement1() {
    $this->runFunctionalTest('foreach-statement-1');
  }
  
  public function testFileAccess1() {
    $this->runFunctionalTest('file-access-1');
  }
  
  public function testPHPExpression1() {
    $this->runFunctionalTest('php-expression-1');
  }
  
  public function testPHPExpression2() {
    $this->runFunctionalTest('php-expression-2');
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