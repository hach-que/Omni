<?php

final class ParseSyntaxTestCase extends PhutilTestCase {
  
  public function testBasicSyntax() {
    $strings = array(
      "echo 'test'",
      "echo 'test'\n",
      "echo 'test' | grep",
      "echo 'test' | grep\n",
      "\$var = \"hello\"",
      "\$var = \"hello\";",
      "\$var = \"hello\"\n",
      "\$var = 'hello'",
      "\$var = 'hello';",
      "\$var = 'hello'\n",
      "echo \$var",
      "echo \$var;",
      "echo \$var\n",
      "\$var = 'hello'; echo \$var\n",
      "\$var = 'hello'; echo \$var;",
      "\$var = 'hello'\n echo \$var;",
      "\$var = 'hello'\n echo \$var\n",
      "\$var = 'hello' \n echo \$var",
      "\$var = 'hello' \n echo \$var;",
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse($str);
      $this->assertTrue(is_array($result), $str);
    }
  }
  
  public function testBackgroundJobs() {
    $strings = array(
      "echo 'test' &",
      "echo 'test' &\n",
      "echo 'test' | grep &",
      "echo 'test' | grep &\n",
      "echo \$var &",
      "echo \$var &;",
      "echo \$var &\n",
      "\$var = 'hello'; echo \$var &\n",
      "\$var = 'hello'; echo \$var &;",
      "\$var = 'hello'\n echo \$var &;",
      "\$var = 'hello'\n echo \$var &\n",
      "\$var = 'hello' \n echo \$var &",
      "\$var = 'hello' \n echo \$var &;",
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse($str);
      $this->assertTrue(is_array($result), $str);
    }
  }
  
  public function testIfStatements() {
    $strings = array(
      "if true { abc }",
      "if true { abc } else { def }",
      <<<EOF
if true {
  abc
}
EOF
      ,
      <<<EOF
if true {
  abc
} else {
  def
}
EOF
      ,
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse($str);
      $this->assertTrue(is_array($result), $str);
    }
  }
  
  public function testExpressions() {
    $strings = array(
      "(test->abc)",
      "(test->abc - ghi)",
      "(\$test->abc - ghi)",
      "(\$test->\$abc - ghi)",
      "(\$test->\$abc - \$ghi)",
      "\$(echo abc)",
      "\$(echo -)",
      "(test->abc->ghi)",
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse($str);
      $this->assertTrue(is_array($result), $str);
    }
  }
  
  public function testArrays() {
    $strings = array(
      "@()",
      "@(test)",
      "@(abc => def)",
      "@(test, test)",
      "@(test, abc => def)",
      "@(abc => def, test)",
      "@(abc => def, abc => def)",
      "@(test,)",
      "@(abc => def,)",
      "@(test, test,)",
      "@(test, abc => def,)",
      "@(abc => def, test,)",
      "@(abc => def, abc => def,)",
      "@( test , )",
      "@( abc => def , )",
      "@( test , test , )",
      "@( test , abc => def , )",
      "@( abc => def , test , )",
      "@( abc => def , abc => def , )",
      "@(abc\n=>\ndef)",
      "@(test,\ntest)",
      "@(test,\nabc\n=>\ndef)",
      "@(abc\n=>\ndef,\ntest)",
      "@(abc\n=>\ndef,\nabc\n=>\ndef)",
      "@(\n)",
      "@(\ntest\n)",
      "@(\nabc\n=>\ndef\n)",
      "@(\ntest,\ntest\n)",
      "@(\ntest,\nabc\n=>\ndef\n)",
      "@(\nabc\n=>\ndef,\ntest\n)",
      "@(\nabc\n=>\ndef,\nabc\n=>\ndef\n)",
      "@(\ntest\n,\n)",
      "@(\nabc\n=>\ndef\n,\n)",
      "@(\ntest\n,\ntest\n,\n)",
      "@(\ntest\n,\nabc\n=>\ndef\n,\n)",
      "@(\nabc\n=>\ndef\n,\ntest\n,\n)",
      "@(\nabc\n=>\ndef\n,\nabc\n=>\ndef\n,\n)",
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse(': '.$str);
      $this->assertTrue(is_array($result), omnilang_get_error().'~ : '.$str);
    }
  }
  
  public function testInvalidSyntax() {
    $strings = array(
      "<?php echo 'test';",
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse(': '.$str);
      $this->assertFalse(is_array($result), omnilang_get_error().'~ : '.$str);
    }
  }
  
  public function testVariableAssignmentFromMethodCall() {
    $strings = array(
      '$test = ($test->call())',
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse(': '.$str);
      $this->assertTrue(is_array($result), omnilang_get_error().'~ : '.$str);
    }
  }
  
  public function testEmptyString() {
    $strings = array(
      'git commit -a -m ""',
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse(': '.$str);
      $this->assertTrue(is_array($result), omnilang_get_error().'~ : '.$str);
    }
  }
  
}