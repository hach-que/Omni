<?php

final class Node extends Phobject {
  
  private static $enableTracing = false;
  
  private $type;
  private $typeInstance;
  private $parent;
  private $children = array();
  private $possibilities = array();
  private $state = null;
  private $possibility;
  private $offset;
  private $length;
  
  public function __construct(
    $type,
    Node $parent = null,
    $possibility = false,
    $noLink = false) {
    
    if (empty($type)) {
      throw new Exception("expected type");
    }
    
    $this->type = $type;
    $this->typeInstance = new $type();
    $this->parent = $parent;
    $this->possibility = $possibility;
    $this->offset = null;
    $this->length = null;
    
    if (!$noLink && $this->parent !== null) {
      if (!$possibility) {
        $this->parent->addChild($this);
      } else {
        $this->parent->addPossibility($this);
      }
    }
  }
  
  public static function setTracing($tracing) {
    self::$enableTracing = $tracing;
  }
  
  public function addChild(Node $child) {
    $this->children[] = $child;
  }
  
  public function getChildren() {
    return $this->children;
  }
  
  public function isPossibility() {
    return $this->possibility;
  }
  
  public function makeReal() {
    $this->possibility = false;
  }
  
  protected function addPossibility(Node $possibility) {
    $this->possibilities[] = $possibility;
  }
  
  public function getPossibilities() {
    return $this->possibilities;
  }
  
  public function getType() {
    return $this->type;
  }
  
  public function getTypeInstance() {
    return $this->typeInstance;
  }
  
  public function getParent() {
    return $this->parent;
  }
  
  public function getOffset() {
    return $this->offset;
  }
  
  public function setOffset($offset) {
    $this->offset = $offset;
    return $this;
  }
  
  public function getLength() {
    return $this->length;
  }
  
  public function setLength($length) {
    $this->length = $length;
    return $this;
  }
  
  public function recalculateLength() {
    if ($this->typeInstance instanceof Symbol) {
      $this->printTrace("recalculating length for ".$this->getType());
      $this->length = 0;
      foreach ($this->children as $child) {
        $child->recalculateLength();
        $this->length += $child->getLength();
      }
    }
  }
  
  public function canAccept($token) {
    $this->printTrace(
      "canAccept called on ".$this->getType().
      " for ".$token);
    
    // Pass off to the symbols for validation.
    $result = $this->typeInstance->canAccept($token);
    if ($result) {
      $this->printTrace($this->getType()." can accept ".$token);
    } else {
      $this->printTrace($this->getType()." CAN NOT accept ".$token);
    }
    return $result;
  }
  
  public function accept(array $token) {
    $offset = $token['offset'];
    $length = $token['length'];
    
    $this->printTrace(
      "accept called on ".$this->getType().
      " for ".$token['token']);
    
    $start = $this->getOffset();
    $end = $this->getOffset() + $this->getLength();
    
    // If the token is outside of our range, ignore it.
    if ($end < $offset || $start > $offset + $length) {
      $this->printTrace("token is outside range!");
      return false;
    }
    
    // See if our current children can do anything with it; we want
    // to perform the tree recalculation at the inner-most 
    foreach ($this->children as $child) {
      if ($child->accept($token)) {
        // One of our children accepted the token.
        $this->printTrace("child accepted token");
        $this->recalculateLength();
        return true;
      }
    }
      
    $this->printTrace("no child accepted token, finding correct location");
    
    // At this point, we might potentially be able to do something 
    // with our token.
    $proposed_children = array();
    foreach ($this->children as $child) {
      $proposed_children[] = $child;
    }
    
    // Create a node for the token.
    $new_child = id(new Node($token['token']))
      ->setOffset($offset)
      ->setLength($length);
    
    if ($offset == $end) {
      // The token is being inserted at the end of our current rule.
      $this->printTrace("token appears at the end of our rule");
      array_push($proposed_children, $new_child);
    } else if ($column + $length == $start) {
      // The token is being inserted at the start of our current rule.
      $this->printTrace("token appears at the start of our rule");
      array_unshift($proposed_children, $new_child);
    } else {
      // The token is somewhere in the middle.  Place it into the 
      // proposed children list where it would fit.
      $c = $start;
      $pos = 0;
      while ($c < $end) {
        if ($column == $c) {
          array_splice($proposed_children, $pos, 0, $new_child);
          break;
        }
        
        $c += $proposed_children[$pos]->getLength();
      }
    }
    
    $old_children = $this->children;
    
    // Recursively traverse the possibility tree, trying to find
    // an absolute match.
    $this->children = 
      $this->traversePossibilitiesForTokens(
        array(),
        $this->getAllLinearTokens($proposed_children),
        $token['token'],
        array());
    
    if ($this->children !== null) {
      $this->printTrace("accepted token");
      $this->recalculateLength();
      return true;
    } else {
      $this->children = $old_children;
      $this->recalculatePossibilities();
      $this->printTrace("not valid syntax; unable to accept token");
      return false;
    }
  }
  
  protected function createStackVisit($children, $available, $new_token_name) {
    $children_stacks = array();
    $available_stacks = array();
    foreach ($children as $child) {
      $children_stacks[] = $child->createStackVisit($child->children, array(), $new_token_name);
    }
    foreach ($available as $avail) {
      $available_stacks[] = $avail->createStackVisit($avail->children, array(), $new_token_name);
    }
    
    $set = array(
      'type' => $this->getType(),
      'children' => $children_stacks,
      'available' => $available_stacks,
      'new-token-name' => $new_token_name);
    return sha1(json_encode($set));
  }
  
  public function traversePossibilitiesForTokens(array $children, array $available, $new_token_name, array $stack_visits) {
    $stack_visit = $this->createStackVisit($children, $available, $new_token_name);
    
    if (in_array($stack_visit, $stack_visits)) {
      $this->printTrace(
        "already visited this combination in the stack; skipping");
      return null;
    }
    
    $stack_visits[] = $stack_visit;
  
    $linear_available = array_unique($this->getAllLinearTokens($available, true));
    foreach ($linear_available as $la) {
      if (!$this->canAccept($la)) {
        $this->printTrace(
          "rejecting all possibilities as there is no way for ".
          $this->getType()." to accept ".$new_token_name);
        return null;
      }
    }
  
    $this->printTrace("beginning recalc");
    
    $old_children = $this->children;
    $this->children = $children;
    $this->recalculatePossibilities();
    
    if (in_array(null, $this->possibilities) && count($available) === 0) {
      // We have the possibility of terminating, and we have no more
      // symbols available.  Take this option now and return the children
      // as-is.
      $this->printTrace("TERMINATION AVAILABLE, TAKING IT...");
      return $children;
    }
    
    if (count($available) === 0) {
      throw new Exception("unexpected: no nodes available for traversal and no termination option");
    }
    
    $this->printTrace("beginning traversal");
    $this->printTrace("children determined: ".count($children));
    $this->printTrace("children available: ".count($available));
    
    // Take one node from the available array.
    $candidate = array_shift($available);
    
    $this->printTrace("got candidate ".$candidate->getType());
    
    $this->printTrace("there are ".count($this->possibilities)." possibilities");
    
    if (count($this->possibilities) === 0) {
      $this->printTrace("RAN OUT OF POSSIBILITIES FOR ".$this->getType().":");
      $this->printTrace("looked like this when evaluating possibilities:");
      $this->printNodeTrace();
      $this->printTrace("expecting failure...");
    } else {
      $this->printTrace("possibilities are:");
      $this->printPossibilities();
    }
    
    $this->printTrace("searching possibilities");
    $possibilities_copy = $this->possibilities;
    foreach ($possibilities_copy as $possibility) {
      if ($possibility === null) {
        // We're not eligible for termination, because we had available symbols
        // when we started.
        continue;
      }
      
      $this->printTrace("possiblity:");
      $possibility->printNodeTrace();
      if ($possibility->getTypeInstance() instanceof Token) {
        $this->printTrace("possiblity is token");
        if ($candidate->getType() === $possibility->getType()) {
          $this->printTrace("possibility token matches!");
          $new_children = $children;
          $new_children[] = id(new Node(
              $candidate->getType(),
              $this,
              false,
              true))
            ->setOffset($candidate->getOffset())
            ->setLength($candidate->getLength());
          if (count($available) === 0) {
            // There are no more available nodes after this, so
            // this has to be a terminating match.
            $this->printTrace("GOT RESULT 1");
            return $new_children;
          } else {
            $this->printTrace("traversing on self token with proposed children");
            $this->printTrace("traversing on self token with children count ".count($new_children));
            $this->printTrace("traversing on self token with children available ".count($available));
            $result = $this->traversePossibilitiesForTokens(
              $new_children,
              $available,
              $new_token_name,
              $stack_visits);
            $this->printTrace("traversal on self token complete");
            if ($result === null) {
              // This possibility does not lead to a successful result.
              $this->printTrace("NEXT POSSIBILITY 4");
              continue;
            } else {
              // This is the successful result;
              $this->printTrace("GOT RESULT 2");
              return $result;
            }
          }
        } else {
          // Not a possible match.
          $this->printTrace("NEXT POSSIBILITY 9");
          continue;
        }
      } else if ($possibility->getTypeInstance() instanceof Symbol) {
        $this->printTrace("INSIDE SYMBOL CHECK");
        $symbol_children = array();
        $available_copy = $available;
        $candidate_token = id(new Node(
            $candidate->getType(),
            $this,
            false,
            true))
          ->setOffset($candidate->getOffset())
          ->setLength($candidate->getLength());
        $possibility_symbol = id(new Node(
            $possibility->getType(),
            $this,
            false,
            true))
          ->setOffset($candidate->getOffset())
          ->setLength($candidate->getLength());
        array_unshift($available_copy, $candidate_token);
        $total_available = count($available_copy);
        
        $this->printTrace("starting symbol check");
        for ($aa = 0; $aa < $total_available; $aa++) {
          $symbol_children[] = array_shift($available_copy);
          
          $this->printTrace(
            "traversing possibility with ".
            count($symbol_children)." available");
          $result = $possibility_symbol->traversePossibilitiesForTokens(
            array(),
            $symbol_children,
            $new_token_name,
            $stack_visits);
          $this->printTrace("traversal complete");
          if ($result === null) {
            // Try with more symbols.
            $this->printTrace("more symbols required");
            continue;
          } else {
            // This gave us a successful result, but does it give
            // us a successful result in our context?
            $this->printTrace("successful result, testing context");
            $possibility_symbol->children = $result;
            $new_children = $children;
            $new_children[] = $possibility_symbol;
            if (count($available_copy) === 0) {
              // There are no more available nodes after this, so
              // this has to be a terminating match.
              $this->printTrace("GOT RESULT 5");
              return $new_children;
            } else {
              $this->printTrace("traversing on self with proposed children");
              $this->printTrace("traversing on self with children count ".count($new_children));
              $this->printTrace("traversing on self with children available ".count($available_copy));
              $this->printTrace("children are:");
              foreach ($new_children as $c99) {
                $c99->printNodeTrace();
              }
              $this->printTrace("available are:");
              foreach ($available as $a99) {
                $a99->printNodeTrace();
              }
              $result = $this->traversePossibilitiesForTokens(
                $new_children,
                $available_copy,
                $new_token_name,
                $stack_visits);
              $this->printTrace("traversal on self complete");
              if ($result === null) {
                // This possibility does not lead to a successful result.
                $this->printTrace("NEXT POSSIBILITY 6");
                continue;
              } else {
                // This is the successful result;
                $this->printTrace("GOT RESULT 7");
                return $result;
              }
            }
          }
        }
        
        // If we get to here, there were no permutation of this symbol
        // that could match...
        $this->printTrace("NO SYMBOL PERMUTATIONS");
        continue;
      } else {
        throw new Exception();
      }
    }
    
    // No possibilities resulted in success.
    $this->printTrace("FAILURE 8");
    $this->children = $old_children;
    return null;
  }
  
  public function getAllLinearTokens(array $proposed_children, $as_tokens = false) {
    // Navigate our children and get all of the tokens in order.
    // This is so that we can modify the list of tokens
    // and consider alternate possibilities.
    $tokens = array();
    foreach ($proposed_children as $child) {
      if (is_array($child)) {
        // Raw token.
        $tokens[] = $child;
        continue;
      }
    
      $instance = $child->getTypeInstance();
      if ($instance instanceof Token) {
        $tokens[] = $child;
        continue;
      } else if ($instance instanceof Symbol) {
        foreach ($child->getAllLinearTokens($child->children) as $subchild) {
          $tokens[] = $subchild;
        }
        continue;
      }
      
      throw new Exception("unknown element in proposed children array");
    }
    
    if ($as_tokens) {
      foreach ($tokens as $key => $token) {
        $tokens[$key] = $token->getType();
      }
    }
    
    return $tokens;
  }
  
  public function recalculatePossibilities() {
    // Remove any current possibilities.
    $this->possibilities = array();
  
    $this->printTrace("recalculating possibilities for ".$this->getType());
  
    if ($this->typeInstance instanceof Token) {
      // There are no possibilities for tokens.
      $this->printTrace("this is a token; no possibilities here");
      return;
    }
  
    // Iterate through all of the available rules and find
    // rules that haven't yet been matched by any of the
    // children we have.
    foreach ($this->typeInstance->getRules() as $rule) {
      if (is_string($rule)) {
        $rule = array($rule);
        $this->printTrace("converted string rule '".$rule[0]."' to array");
      }
      
      $this->printTrace("considering rule ".$this->formatRule($rule));
      
      if (count($this->children) > count($rule)) {
        // This rule matches less tokens than we currently
        // have, so it's no longer a possibility.
        $this->printTrace("have more children than rule count");
        continue;
      }
      
      // If we've already consumed tokens, we have to make
      // sure that the children match the rule in order for
      // it to be considered.
      $prefix_matches = true;
      for ($i = 0; $i < count($this->children); $i++) {
        if ($rule[$i] !== $this->children[$i]->getType()) {
          $this->printTrace($rule[$i]." !== ".$this->children[$i]->getType());
          $prefix_matches = false;
          break;
        }
      }
      if (!$prefix_matches) {
        $this->printTrace("prefix does not match for rule");
        continue;
      }
      
      if (count($this->children) === count($rule)) {
        // We are a terminating possibility, so add
        // null to the list.
        $this->possibilities[] = null;
        $this->printTrace("terminating possibility, adding null");
        continue;
      }
      
      // We've passed all the checks; create the new
      // possible node.
      $possibility = $rule[count($this->children)];
      $node = new Node(
        $possibility,
        $this,
        true);
      
      // Determine if the possible node has already been
      // traversed as part of the parent, in which case
      // we don't expand it.
      $parents = array();
      $current = $this;
      while ($current !== null) {
        $parents[$current->getType()] = true;
        $current = $current->getParent();
      }
      
      // Recalculate possibilities if permitted.
      if (!isset($parents[$possibility])) {
        $node->recalculatePossibilities();
      }
    }
  
    $this->printTrace(
      "there are now ".count($this->possibilities).
      " possibilities for ".$this->getType());
  }
  
  public function printNode($indent = "") {
    echo $indent."- ".$this->getType();
    echo " (at ".$this->getOffset().", length ".$this->getLength().")\n";
    foreach ($this->children as $child) {
      $child->printNode($indent."  ");
    }
  }
  
  private function printNodeTrace() {
    if (!self::$enableTracing) {
      return;
    }
    
    $this->printNode();
  }
  
  public function printPossibilities($indent = "", $alwaysShow = false) {
    if (!self::$enableTracing && !$alwaysShow) {
      return;
    }
    
    echo $indent."- ".$this->getType()."\n";
    foreach ($this->possibilities as $child) {
      if ($child === null) {
        $this->printTrace("<TERMINATION>");
      } else {
        $child->printPossibilities($indent."  ", $alwaysShow);
      }
    }
  }
  
  private function printSuffix() {
    if ($this->parent !== null) {
      echo $this->parent->printSuffix().' > ';
    }
    
    echo $this->getType();
  }
  
  private function printTrace($message) {
    if (!self::$enableTracing) {
      return;
    }
    
    $this->printSuffix();
    echo ': '.$message."\n";
  }
  
  private function formatRule($rule) {
    return implode(' + ', $rule);
  }
  
  /*
  public function recalculateTreeFromTokens($tokens) {
    // Reset all children.
    $this->children = array();
    
    // Recalculate all possibilities.
    $this->recalculatePossibilities();
    
    // Determine what rule the tokens will fit into.
    foreach ($this->getTypeInstance()->getRules() as $rule) {
      if (is_string($rule)) {
        $rule = array($rule);
      }
      
      $rule_instances = array();
      foreach ($rule as $component) {
        $rule_instances[] = new $component();
      }
      
      $rule_position = 0;
      foreach ($tokens as $token) {
        $inst = $rule_instances[$rule_position];
        
        
      }
      
      /*
      
      // For each of the token rule instances, try to fit
      // our list of tokens into them.  That is, fit the
      // following tokens like so:
      //
      //   A --- B --- A --- A --- C --- D --- A
      //   |           |                 |     |
      //  [A] - sym - [A] ---- sym ---- [D] - [A]
      //
      // TODO: Note that the following is also a possibility:
      //
      //   A --- B --- A --- A --- C --- D --- A
      //   |                 |           |     |
      //  [A] ---- sym ---- [A] - sym - [D] - [A]
      //
      // if that would result in a valid tree.
      //
      // Symbol placements can match any number of tokens,
      // but will invalidate the considered tree if the tokens
      // don't match the symbol's rules.
      $rule_position = 0;
      $token_position = 0;
      $rule_bins = array();
      $valid = true;
      foreach ($rule_instances as $inst) {
        $type = '';
        if ($inst instanceof Token) {
          $type = 'token';
        } else if ($inst instanceof Symbol) {
          $type = 'symbol';
        } else {
          throw new Exception();
        } 
        $rule_bins[] = array(
          'type' => $type,
          'tokens' => array()
        );
      }
      
      while ($rule_position < count($rule_instances) &&
        $rule_instances[$rule_position] instanceof Symbol) {
        $rule_position++;
      }
      
      for ($token_position = 0; $token_position < count($tokens); $token_position++) {
        $inst = $rule_instances[$rule_position];
        $token = $tokens[$token_position];
        
        if ($inst instanceof Token) {
        }
        if (get_class($token) === get_class($inst)) {
          // We have an exact match on this position.
          $rule_bins[$rule_position]['tokens'][] = $token;
          $rule_position++;
        } else {
          // This token doesn't match; see if there are any
          // sequentially previous symbols we can put it into.
          $filled = false;
          for ($a = $rule_position; $a >= 0; $a--) {
            if ($rule_instances[$a] instanceof Symbol) {
              $rule_bins[$a]['tokens'][] = $token;
              $filled = true;
              break;
            }
            
            if ($a !== $rule_position && !($rule_instances[$a] instanceof Symbol)) {
              // If we encounter a previous token in the rule, then that
              // token must have already been filled, so we can't move tokens
              // further back.
              break;
            }
          }
          
          if (!$filled) {
            $valid = false;
          }
        }
        
        if (!$valid) {
          break;
        }
      }
      
      if ($valid) {
        foreach ($rule_bins as $bin) {
          $node = new Node(
            $bin['type'],
            /* TODO * 0,
            /* TODO * 0,
            $this,
            false);
          $node->accept($bin['tokens']);
        }
      }
      
      $considered_tree = array();
      $has_symbol_before = false;
      foreach ($rule_instances as $inst) {
        if ($inst instanceof Token) {
          // Tokens must match explicitly.
          if (get_class($inst) === get_class($tokens);
        }
      }
      *
    }
  }
  */
  
  private function getSymbolTypes() {
    static $symbolTypes = null;
    if ($symbolTypes === null) {
      $symbolTypes = id(new PhutilSymbolLoader())
        ->setAncestorClass('Symbol')
        ->loadObjects();
    }
    return $symbolTypes;
  }
  
}