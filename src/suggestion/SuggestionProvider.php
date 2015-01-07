<?php

abstract class SuggestionProvider extends Phobject {

  abstract function getSuggestions(Shell $shell, $current, $context);

}