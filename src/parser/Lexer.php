<?php

final class Lexer extends Phobject {

  const TOKEN_UNKNOWN = 'unknown';
  const TOKEN_EOF = 'eof';

  private $tokenTypes;
  private $text;
  private $currentOffset;
  private $terminating;
  
  public function __construct() {
    $this->tokenTypes = id(new PhutilSymbolLoader())
      ->setAncestorClass('Token')
      ->loadObjects();
  }
  
  public function load($source_text) {
    $this->text = $source_text;
    $this->currentOffset = 0;
    $this->terminating = false;
  }
  
  public function next() {
    if ($this->terminating) {
      return self::TOKEN_EOF;
    }
    
    $result = self::TOKEN_EOF;
    
    if ($this->currentOffset < strlen($this->text)) {
      $result = $this->match($this->text, $this->currentOffset);
      if ($result === false) {
        return self::TOKEN_UNKNOWN;
      }
      $this->currentOffset += strlen($result['match']);
    }
    
    if ($this->currentOffset >= strlen($this->text)) {
      $this->terminating = true;
    }
    
    return $result;
  }
  
  private function match($line, $offset) {
    $target = substr($line, $offset);
    
    foreach ($this->tokenTypes as $token_type) {
      if (preg_match($token_type->getRegex(), $target, $matches)) {
        return array(
          'match' => $matches[1],
          'token' => $token_type->getName(),
          'offset' => $offset,
          'length' => strlen($matches[1]),
        );
      }
    }
    
    return false;
  }

}