<?php

$str = hex2bin('8188');

echo (ord($str[0]) & 0x80);
