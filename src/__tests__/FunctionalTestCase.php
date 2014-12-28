<?php

final class FunctionalTestCase extends PhutilTestCase {

  public function testNestedScriptsBackground() {
    $expect = <<<EOF
a: bg
b: bg
b: fg
b: end
a: fg
b: bg
b: fg
b: end
a: end
EOF;
    $this->runFunctionalTest('nested-scripts-background', $expect);
  }
  
  public function testNestedScriptsForeground() {
    $expect = <<<EOF
a: fg
b: fg
b: end
a: end
EOF;
    $this->runFunctionalTest('nested-scripts-foreground', $expect);
  }
  
  private function runFunctionalTest($name, $expect) {
    $omni = phutil_get_library_root('omni').'/../bin/omni';
    $cwd = phutil_get_library_root('omni').'/../test/'.$name;
  
    list($err, $stdout, $stderr) = id(new ExecFuture('%C ./run.sh', $omni))
      ->setCWD($cwd)
      ->resolve();
    $this->assertEqual(0, $err);
    $this->assertEqual($stdout, $expect."\n");
  }
  
}