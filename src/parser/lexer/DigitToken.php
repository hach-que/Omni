<?php

final class LetterToken extends Token {

  public function getRegex() {
    return '/^([a-zA-Z])/';
  }

}