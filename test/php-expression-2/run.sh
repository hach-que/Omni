#!/bin/omni

: <?php

class MyGlobal {
  public static $value;
}

MyGlobal::$value = "test";

?>

echo (<?php return MyGlobal::$value; ?>)
