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
      $this->assertTrue(is_array($result));
    }
  }
  
}