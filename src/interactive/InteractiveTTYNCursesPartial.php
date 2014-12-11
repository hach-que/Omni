<?php

final class InteractiveTTYNCursesPartial extends Phobject {

  private $shell;
  private $parser;
  private $exited;
  private $ttyAttrs;
  private $ncursesAttrs;
  private $rootWindow;
  private $suggestionWindow;
  private $inputX;
  private $inputY;
  private $prompt;

  public function run() {
    $this->shell = new Shell();
    $this->parser = new Parser();
    
    // Start NCurses, but allow normal terminal programs to operate correctly.
    $this->ttyAttrs = omni_tcgetattr(0);
    ncurses_init();
    $this->ncursesAttrs = omni_tcgetattr(0);
    $this->restoreTerminal();
    ncurses_halfdelay(1);
    ncurses_noecho();
    ncurses_refresh();
    
    $this->rootWindow = ncurses_newwin(0, 0, 0, 0);
    
    $this->prompt = "#> ";
    
    while (!$this->exited) {
      ncurses_getmaxyx($this->rootWindow, $height, $width);
      ncurses_mvwaddstr($this->rootWindow, $height - 1, 0, $this->prompt);
      ncurses_wrefresh($this->rootWindow);
      $this->inputX = strlen($this->prompt);
      $this->inputY = $height - 1;
      
      $has_command = false;
      while (!$has_command) {
        $character = ncurses_getch();
        switch ($character) {
          case -1:
            {
              // No key has been pressed; update the suggestions.
              break;
            }
          case NCURSES_KEY_BACKSPACE:
            {
              if ($this->inputX > strlen($this->prompt)) {
                $this->inputX--;
                ncurses_wmove($this->rootWindow, $this->inputY, $this->inputX);
                ncurses_delch();
                ncurses_wrefresh($this->rootWindow);
              }
              break;
            }
          case 13:
            {
              $this->inputX = 0;
              $has_command = true;
              ncurses_scrl(-1);
              /*$this->restoreTerminal();
              echo "\n";
              $this->restoreNcurses();
              ncurses_clear();
              ncurses_wrefresh($this->rootWindow);
              if ($this->suggestionWindow !== null) {
                ncurses_wrefresh($this->suggestionWindow);
              }*/
              break;
            }
          default:
            {
              // TODO add the key to the buffer.
              //$this->restoreTerminal();
              $this->inputX++;
              ncurses_waddch($this->rootWindow, $character);
              ncurses_wrefresh($this->rootWindow);
              break;
            }
        }
        
        if (!$has_command) {
          $this->updateSuggestions();
        }
      }
    }
    
    $this->restoreTerminal();
    
    $this->shell->run();
    
    $this->quit();
  }
  
  public function updateSuggestions() {
    ncurses_getyx($this->rootWindow, $y, $x);
  
    if ($this->suggestionWindow === null) {
      $this->suggestionWindow = ncurses_newwin(5, 25, $y - 5, $this->inputX);
    } else {
      ncurses_delwin($this->suggestionWindow);
      $this->suggestionWindow = ncurses_newwin(5, 25, $y - 5, $this->inputX);
    }
    
    ncurses_wborder(
      $this->suggestionWindow,
      0, 0, 0, 0,
      0, 0, 0, 0);
    ncurses_wrefresh($this->suggestionWindow);
    //ncurses_waddstr($this->suggestionWindow, " y: ".$y." x: ".$x);
      
    ncurses_move($this->inputY, $this->inputX);
  }
  
  public function restoreTerminal() {
    omni_tcsetattr_tcsanow(
      0, 
      $this->ttyAttrs['iflag'],
      $this->ttyAttrs['oflag'],
      $this->ttyAttrs['lflag']);
  }
  
  public function restoreNcurses() {
    omni_tcsetattr_tcsanow(
      0, 
      $this->ncursesAttrs['iflag'],
      $this->ncursesAttrs['oflag'],
      $this->ncursesAttrs['lflag']);
  }
  
  public function quit() {
    ncurses_end();
  }

}