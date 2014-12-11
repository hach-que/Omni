<?php

final class WordOrNumberSymbol extends Symbol {

  public function getRules() {
    return array(
      'WordSymbol',
      'NumberSymbol',
    );
  }

}