<?php

final class WhitespaceToken extends Token {

  public function getRegex() {
    return '/^(\s+)/';
  }
  
}