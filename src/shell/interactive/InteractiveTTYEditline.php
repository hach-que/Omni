<?php

final class InteractiveTTYEditline extends Phobject {

  private $shell;
  private $parser;
  private $maxSuggestions = 0;
  private $simulate;
  private $raw = false;
  private $continuingLine = false;
  private $continuingLineBuffer = '';
  private $visibleSuggestionsEnabled = false;
  private $suggestions = array();
  
  public function setRaw($raw) {
    $this->raw = $raw;
  }
  
  public function simulate() {
    $this->shell = new Shell();
    $this->shell->initialize();
    
    $this->simulate = true;
    
    $this->handleCommand('echo "test" | grep "t"');
    $this->handleCommand('echo "test" | grep "ll"');
    $this->handleCommand('echo "hello" | grep "t"');
    $this->handleCommand('echo "hello" | grep "ll"');
    $this->handleCommand('sleep 1');
    $this->handleCommand('job');
    $this->handleCommand('job');
    $this->handleCommand('echo "hello" | grep "ll"');
    
    $this->shell->finalize();
  }

  public function run() {
    $this->shell = new Shell();
    $this->shell->initialize();
    
    if (!$this->raw) {
      editline_init();
      editline_begin($this->getPrompt());
      
      $this->recalculateSuggestions('', 0);
      $this->renderVisualSuggestions();
    }
    
    while (!$this->shell->wantsToExit()) {
      if ($this->raw) {
        echo "#RAW> ";
        $fd = 0;
        fd_set_blocking($fd, true);
        $buffer = '';
        $char = null;
        while ($char !== "\n") {
          $char = fd_read($fd, 1);
          if ($char === null) {
            // Pipe closed.
            throw new Exception('stdin is closed! :(');
          }
          if ($char === false) {
            throw new Exception('read error on stdin: '.idx(fd_get_error(), 'error'));
          }
          if ($char !== "\n") {
            $buffer .= $char;
          }
        }
        $result = $buffer;
        
        $this->handleCommand($result);
      } else {
        $result = editline_read();
      
        switch ($result['status']) {
          case 'visual-suggest':
            $this->clearVisualSuggestions();
            $this->renderFullSuggestions();
            break;
          case 'typing':
          case 'cancelled':
            $this->recalculateSuggestions($result['input'], $result['cursor']);
            $this->renderVisualSuggestions();
            break;
          case 'complete':
            $this->handleCommand($result['input']);
            
            if (!$this->shell->wantsToExit()) {
              $this->recalculateSuggestions('', 0);
              $this->renderVisualSuggestions();
            }
            break;
          default:
            throw new Exception('Unknown status '.$result['status']);
        }
      }
    }
    
    $this->shell->finalize();
  }
  
  public function recalculateSuggestions($input, $position) {
    $engine = new SuggestionEngine();
    $this->suggestions = 
      $engine->getSuggestions($this->shell, $input, $position);
    
    $this->calculateAutocomplete($input, $this->suggestions);
  }
  
  public function renderFullSuggestions() {
    $table = id(new PhutilConsoleTable())
      ->addColumn('name', array('title' => 'Name'))
      ->addColumn('description', array('title' => 'Description'));
    
    foreach ($this->suggestions as $suggestion) {
      $table->addRow(array(
        'name' => $suggestion['node_replace'],
        'description' => $suggestion['description'],
      ));
    }
    
    echo "\n";
    $table->draw();
    editline_reprompt();
  }
  
  public function renderVisualSuggestions() {
    if (!$this->visibleSuggestionsEnabled) {
      return;
    }
    
    $suggestions = $this->suggestions;
    
    if (count($suggestions) > 5) {
      $suggestions = array_slice($suggestions, 0, 5);
    }
    
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
      $p = $suggestions[$i]['node_replace'].' - '.$suggestions[$i]['description'];
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
  
  public function calculateAutocomplete($input, array $suggestions) {
    $autocomplete = array();
    
    if (count($suggestions) > 0) {
      // Limit to only the highest priority.
      $max_priority = 0;
      foreach ($suggestions as $key => $value) {
        if ($value['priority'] > $max_priority) {
          $max_priority = $value['priority'];
        }
      }
      foreach ($suggestions as $key => $value) {
        if ($value['priority'] !== $max_priority) {
          unset($suggestions[$key]);
        }
      }
    }
    
    foreach ($suggestions as $suggestion) {
      $autocomplete[] = $suggestion['append'];
    }
    
    editline_autocomplete_set($autocomplete);
  }
  
  public function clearVisualSuggestions() {
    if (!$this->visibleSuggestionsEnabled) {
      return;
    }
    
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
    
    if (!$this->raw && !$this->simulate) {
      $this->clearVisualSuggestions();
    }
    
    omni_trace("execute input");
    
    try {
      $this->shell->execute($this->continuingLineBuffer.$input);
      $this->addCommandToHistory($input);
      $this->continuingLine = false;
      $this->continuingLineBuffer = '';
    } catch (StatementNotTerminatedException $ex) {
      $this->continuingLine = true;
      $this->continuingLineBuffer .= substr($input, 0, strlen($input) - 1).' ';
    } catch (Exception $ex) {
      phlog($ex);
      $this->addCommandToHistory($input);
      $this->continuingLine = false;
      $this->continuingLineBuffer = '';
    }
    
    if (!$this->raw && !$this->shell->wantsToExit()) {
      omni_trace("begin editline again");
      
      if (!$this->simulate) {
        $this->reprompt();
      }
      
      omni_trace("ready for editline input");
    }
  }
  
  private function reprompt() {
    editline_end();
    if ($this->continuingLine) {
      editline_begin(">> ");
    } else {
      editline_begin($this->getPrompt());
    }
  }
  
  private function addCommandToHistory($input) {
    if (strlen(trim($this->continuingLineBuffer.$input)) > 0) {
      editline_history_add($this->continuingLineBuffer.$input);
    }
  }

  public function getPrompt() {
    $user = get_current_user();
    $host = gethostname();
    $cwd = getcwd();
    $home = getenv('HOME');
    if (strlen($cwd) >= strlen($home)) {
      if (substr($cwd, 0, strlen($home)) === $home) {
        $cwd = '~'.substr($cwd, strlen($home));
      }
    }
    
    return '[omni] '.$user.'@'.$host.':'.$cwd.'> ';
  }
  
}