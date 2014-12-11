<?php

final class RootSymbol extends Symbol {

  public function getRules() {
    return array(
      'JoinedWordOrNumberSymbol',
    );
  }

}