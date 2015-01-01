#!/bin/omni

: <?php
global $MYGLOBAL;
$MYGLOBAL = "test";
?>

echo (<?php
global $MYGLOBAL;
return $MYGLOBAL;
?>)
