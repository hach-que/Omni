<?php

final class InteractiveTTYEditline extends Phobject {

  private $shell;
  private $parser;
  private $maxSuggestions = 0;

  public function run() {
    $this->shell = new Shell();
    $this->shell->initialize();
    
    editline_begin();
    
    $this->renderSuggestions('');
    
    while (true) {
      $result = editline_read();
      
      switch ($result['status']) {
        case 'typing':
        case 'cancelled':
          $this->renderSuggestions($result['input']);
          break;
        case 'complete':
          $this->handleCommand($result['input']);
          $this->renderSuggestions('');
          break;
      }
    }
  }
  
  public function renderSuggestions($input) {
    $suggestions = $this->getSuggestionsForInput($input);
    
    if ($this->maxSuggestions < count($suggestions)) {
      $this->maxSuggestions = count($suggestions);
    }
    
    // Make sure we have enough render room.
    for ($i = 0; $i < count($suggestions) + 2; $i++) { echo "\x1BD"; }
    for ($i = 0; $i < count($suggestions) + 2; $i++) { echo "\x1BM"; }
    
    echo "\x1B7";
    echo "\x1B8\x1B[1B\x1B[2K";
    echo "\x1B8\x1B[1B-- Suggestions --";
    for ($i = 0; $i < count($suggestions); $i++) {
      $p = $suggestions[$i];
      $x = $i + 2;
      echo "\x1B8\x1B[".$x."B\x1B[2K";
      echo "\x1B8\x1B[".$x."B".$p;//->getType();
    }
    for ($i = count($suggestions); $i < $this->maxSuggestions; $i++) {
      $x = $i + 2;
      echo "\x1B8\x1B[".$x."B\x1B[2K";
    }
    echo "\x1B8";
  }
  
  public function clearSuggestions() {
    echo "\x1B7";
    echo "\x1B8\x1B[2K";
    echo "\x1B8\x1B[1B\x1B[2K";
    for ($i = 0; $i < $this->maxSuggestions; $i++) {
      $x = $i + 2;
      echo "\x1B8\x1B[".$x."B\x1B[2K";
    }
    echo "\x1B8";
  }
  
  public function handleCommand($input) {
    omni_trace("clear suggestions");
    
    $this->clearSuggestions();
    
    omni_trace("execute input");
    
    $this->shell->execute($input);
    
    omni_trace("begin editline again");
    
    editline_begin();
    
    omni_trace("ready for editline input");
  }

  public function getPrompt() {
    $user = get_current_user();
    $host = gethostname();
    $cwd = getcwd();
    
    return $user."@".$host.":".$cwd."> ";
  }
  
  private function getSuggestionsForInput($input) {
    list($err, $stdout, $stderr) = 
      id(new ExecFuture("whatis -s 1 -r ^%s", $input))
        ->resolve();
  
    if ($err !== 0) {
      return array($input);
    }
  
    $lines = phutil_split_lines($stdout);
    return array_slice($lines, 0, 5); 
  }
  
}