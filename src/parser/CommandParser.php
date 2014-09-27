<?php

final class CommandParser {
  
  const QUOTE_NONE = 'none';
  const QUOTE_SINGLE = 'single';
  const QUOTE_DOUBLE = 'double';
  
  public function parse($text) {
    $components = array();
    
    $len = strlen($text);
    $quote_mode = self::QUOTE_NONE;
    $buffer = '';
    $next_escape = false;
    for ($i = 0; $i < $len; $i++) {
      $c = $text[$i];
      
      if ($next_escape) {
        $buffer .= $c;
        $next_escape = false;
        continue;
      }
      
      switch ($c) {
        case '\'':
          if ($quote_mode === self::QUOTE_NONE) {
            $quote_mode = self::QUOTE_SINGLE;
          } else if ($quote_mode === self::QUOTE_SINGLE) {
            $quote_mode = self::QUOTE_NONE;
          } else {
            $buffer .= $c;
          }
          break;
        case '\\':
          if ($quote_mode === self::QUOTE_SINGLE) {
            $buffer .= $c;
          } else {
            $next_escape = true;
          }
          break;
        case '"':
          if ($quote_mode === self::QUOTE_DOUBLE) {
            $quote_mode = self::QUOTE_NONE;
          } else if ($quote_mode === self::QUOTE_NONE) {
            $quote_mode = self::QUOTE_DOUBLE;
          } else {
            $buffer .= $c;
          }
          break;
        case ' ':
          if ($quote_mode === self::QUOTE_NONE) {
            $components[] = $buffer;
            $buffer = '';
          } else {
            $buffer .= $c;
          }
          break;
        default:
          $buffer .= $c;
          break;
      }
    }
    
    if (strlen($buffer) > 0) {
      $components[] = $buffer;
      $buffer = '';
    }
    
    return $components;
  }
  
}