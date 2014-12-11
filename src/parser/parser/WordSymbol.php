<?php

final class WordSymbol extends Symbol {

  public function getRules() {
    return array(
      'LetterToken',
      array('WordSymbol', 'LetterToken'),
      array('WordSymbol', 'DigitToken'),
    );
  }

}