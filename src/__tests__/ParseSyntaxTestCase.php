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
    );
    
    foreach ($strings as $str) {
      $result = omnilang_parse($str);
      $this->assertTrue(is_array($result), $str);
    }
  }
}