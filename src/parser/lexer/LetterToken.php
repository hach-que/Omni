<?php

final class DigitToken extends Token {

  public function getRegex() {
    return '/^([0-9])/';
  }

}