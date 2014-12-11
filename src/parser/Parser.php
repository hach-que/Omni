<?php

final class Parser extends Phobject {

  const EXIT_UNKNOWN_TOKEN = 'unknown-token';
  const EXIT_EOF = 'eof';
  const EXIT_BAD_ACCEPT = 'bad-accept';
  const EXIT_SUCCESS = 'success';
  
  private $symbolTypes;
  private $lexer;
  private $root;
  private $exitResult;

  public function __construct() {
    $this->symbolTypes = id(new PhutilSymbolLoader())
      ->setAncestorClass('Symbol')
      ->loadObjects();
  }

  public function load($text) {
    $this->lexer = id(new Lexer());
    $this->lexer->load($text);
    
    $this->root = id(new Node('RootSymbol'))
      ->setOffset(0)
      ->setLength(0);
    $this->root->recalculatePossibilities();
  }
  
  public function next() {
    $start = microtime(true);
    $token = $this->lexer->next();
    
    if ($token === Lexer::TOKEN_EOF) {
      $this->exitResult = self::EXIT_EOF;
      return false;
    }
    
    if ($token === Lexer::TOKEN_UNKNOWN) {
      $this->exitResult = self::EXIT_UNKNOWN_TOKEN;
      return false;
    }
    
    $accept = microtime(true);
    if (!$this->root->accept($token)) {
      $this->exitResult = self::EXIT_BAD_ACCEPT;
      return false;
    }
    
    $end = microtime(true);
    
    $this->exitResult = self::EXIT_SUCCESS;
    return true;
  }
  
  public function getRoot() {
    return $this->root;
  }

}