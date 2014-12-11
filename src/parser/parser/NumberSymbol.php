<?php

final class NumberSymbol extends Symbol {

  public function getRules() {
    return array(
      'DigitToken',
      array('NumberSymbol', 'DigitToken')
    );
  }

}