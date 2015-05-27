<?php

final class FunctionalTestCase extends PhutilTestCase {
  
  public function testAnd1() {
    $this->runFunctionalTest('and-1');
  }
  
  public function testAnd2() {
    $this->runFunctionalTest('and-2');
  }
  
  public function testAnd3() {
    $this->runFunctionalTest('and-3');
  }
  
  public function testAnd4() {
    $this->runFunctionalTest('and-4');
  }
  
  public function testAnd5() {
    $this->runFunctionalTest('and-5', array('--trace'));
  }
  
  public function testAnd6() {
    $this->runFunctionalTest('and-6');
  }
  
  public function testAnd7() {
    $this->runFunctionalTest('and-7');
  }
  
  public function testArgumentsString1() {
    $this->runFunctionalTest('arguments-string-1', array('asdf'));
  }
  
  public function testArgumentsNumeric1() {
    $this->runFunctionalTest('arguments-numeric-1', array('123'));
  }
  
  public function testAssignment1() {
    $this->runFunctionalTest('assignment-1');
  }
  
  public function testAssignment2() {
    $this->runFunctionalTest('assignment-2');
  }
  
  public function testAssignment3() {
    $this->runFunctionalTest('assignment-3');
  }
  
  public function testAssignment4() {
    $this->runFunctionalTest('assignment-4');
  }
  
  public function testAssignment5() {
    $this->runFunctionalTest('assignment-5');
  }
  
  public function testCaptureExitCode() {
    $this->runFunctionalTest('capture-exit-code');
  }
  
  public function testCaptureLambda() {
    $this->runFunctionalTest('capture-lambda');
  }
  
  public function testExecFuture1() {
    $this->runFunctionalTest('exec-future-1');
  }
  
  public function testExitCodeVar0() {
    $this->runFunctionalTest('exit-code-var-0');
  }
  
  public function testExitCodeVar1() {
    $this->runFunctionalTest('exit-code-var-1');
  }
  
  public function testFileAccess1() {
    $this->runFunctionalTest('file-access-1');
  }
  
  public function testForLoop1() {
    $this->runFunctionalTest('for-loop-1');
  }
  
  public function testForeachStatement1() {
    $this->runFunctionalTest('foreach-statement-1');
  }
  
  public function testFunction1() {
    $this->runFunctionalTest('function-1');
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
  
  public function testIterDoesNotDeadlock() {
    $this->assertEqual('', 'This deadlocks due to the new && and || operators.');
    return;
    
    $this->runFunctionalTest('iter-does-not-deadlock');
  }
  
  public function testLsExitCode0() {
    $this->runFunctionalTest('ls-exit-code-0');
  }
  
  public function testLsExitCode1() {
    $this->runFunctionalTest('ls-exit-code-1');
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
  
  public function testOr1() {
    $this->runFunctionalTest('or-1');
  }
  
  public function testOr2() {
    $this->runFunctionalTest('or-2');
  }
  
  public function testOr3() {
    $this->runFunctionalTest('or-3');
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
  
    list($err, $stdout, $stderr) = id(new ExecFuture('%C %s %Ls', $omni, $cwd.'/run.sh', $args))
      ->setCWD($cwd)
      ->resolve();
    $this->assertEqual(0, $err);
    $this->assertEqual($expect, $stdout);
  }
  
}