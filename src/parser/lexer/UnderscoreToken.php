<?php

final class UnderscoreToken extends Token {

  public function getRegex() {
    return '/^(_)/';
  }

}