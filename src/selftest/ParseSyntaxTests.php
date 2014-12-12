<?php

final class ParseSyntaxTests extends Tests {
  
  public function runTests() {
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
    
    echo "Parse syntax tests:\n";
    echo "=========================\n";
    
    foreach ($strings as $str) {
      $result = omnilang_parse($str);
      if (is_array($result)) {
        $this->createPass($str);
      } else {
        $this->createFail($str);
      }
    }
  }
  
}