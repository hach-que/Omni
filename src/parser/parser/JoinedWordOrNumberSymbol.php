<?php

final class JoinedWordOrNumberSymbol extends Symbol {

  public function getRules() {
    return array(
      'WordOrNumberSymbol',
      array('JoinedWordOrNumberSymbol', 'WhitespaceToken'),
      array('JoinedWordOrNumberSymbol', 'WhitespaceToken', 'JoinedWordOrNumberSymbol'),
    );
  }

}