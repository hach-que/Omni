<?php

final class GitBranchSuggestionProvider extends SuggestionProvider {

  public function getSuggestions(Shell $shell, $current, $context) {
    if ($current['type'] !== 'fragments') {
      return array();
    }
    
    $arguments = array_pop($context);
    if ($arguments['type'] !== 'arguments') {
      return array();
    }
    
    $command = array_pop($context);
    if ($command['type'] !== 'command') {
      return array();
    }
    
    $visitor = id(new FragmentsVisitor())
      ->setAllowSideEffects(false);
    $executable = 
      $visitor->visit($shell, $arguments['children'][0]);
    $safe_to_append = 
      $visitor->isSafeToAppendFragment($shell, $arguments['children'][0]); 
    
    if ($executable !== 'git') {
      return array();
    }
    
    $git_global_args = array(
      array('--version'),
      array('--help'),
      array('-C', '<path>'),
      array('-c', '<pair>'),
      array('--exec-path', '<path>'),
      array('--html-path'),
      array('--man-path'),
      array('--info-path'),
      array('-p'),
      array('--paginate'),
      array('--no-pager'),
      array('--no-replace-objects'),
      array('--bare'),
      array('--git-dir', '<path>'),
      array('--work-tree', '<path>'),
      array('--namespace', '<name>'),
    );
    
    $command = null;
    $command_pos = null;
    $accepting_argument = false;
    $main_args = array();
    for ($i = 1; $i < count($arguments['children']); $i++) {
      $value = id(new FragmentsVisitor())
        ->setAllowSideEffects(false)
        ->visit($shell, $arguments['children'][$i]);
      $main_args[] = $value;
        
      if ($accepting_argument) {
        $accepting_argument = false;
        continue;
      }
      
      $value = explode('=', $value, 2);
      $value = $value[0];
      
      $is_argument = false;
      foreach ($git_global_args as $arg) {
        if ($arg[0] === $value) {
          if (count($arg) > 1 && $arg[0][1] !== '-') {
            $accepting_argument = true;
          }
          
          $is_argument = true;
          break;
        }
      }
      
      if (!$is_argument) {
        $command = $value;
        $command_pos = $i;
        break;
      }
    }
    
    if ($command === null) {
      return array();
    }
    
    $expects_branch = false;
    $last_component = null;
    
    // Remove the actual Git command from the main args.
    array_pop($main_args);
    
    switch ($command) {
      case 'checkout':
        $checkout_args = array(
          array('-q'),
          array('-f'),
          array('-m'),
          array('--detach'),
          array('-p', 'cancel'),
          array('--patch', 'cancel'),
          array('--ours', 'cancel'),
          array('--theirs', 'cancel'),
          array('-m', 'cancel'),
          array('--conflict', 'cancel'),
        );
        
        for ($i = $command_pos + 1; $i < count($arguments['children']); $i++) {
          $value = id(new FragmentsVisitor())
            ->setAllowSideEffects(false)
            ->visit($shell, $arguments['children'][$i]);
          $original_value = $value;
          $value = explode('=', $value, 2);
          $value = $value[0];
          
          $is_argument = false;
          foreach ($checkout_args as $arg) {
            if ($arg[0] === $value) {
              if (count($arg) > 1 && $arg[1] === 'cancel') {
                return array();
              } else {
                // This is an expected argument.
                $is_argument = true;
                break;
              }
            }
          }
          
          if (!$is_argument) {
            // This is a position we expect a branch name.
            if ($arguments['children'][$i] === $current) {
              $expects_branch = true;
              $last_component = $original_value;
            }
          }
        }
        
        break;
      default:
        // Attempt to autocomplete the Git subcommand.
        list($stdout, $stderr) = id(new ExecFuture('git %Ls help -a', $main_args))
          ->resolvex();
          
        $low_level = array(
          'applymbox',
          'applypatch',
          'archimport',
          'cat-file',
          'check-attr',
          'check-ignore',
          'check-mailmap',
          'check-ref-format',
          'checkout-index',
          'commit-tree',
          'count-objects',
          'credential-cache',
          'credential-store',
          'cvsexportcommit',
          'cvsimport',
          'cvsserver',
          'daemon',
          'diff-files',
          'diff-index',
          'diff-tree',
          'fast-import',
          'fast-export',
          'fsck-objects',
          'fetch-pack',
          'fmt-merge-msg',
          'for-each-ref',
          'hash-object',
          'http-*',
          'index-pack',
          'init-db',
          'local-fetch',
          'ls-files',
          'ls-remote',
          'ls-tree',
          'mailinfo',
          'mailsplit',
          'merge-*',
          'mktree',
          'mktag',
          'pack-objects',
          'pack-redundant',
          'pack-refs',
          'parse-remote',
          'patch-id',
          'prune',
          'prune-packed',
          'quiltimport',
          'read-tree',
          'receive-pack',
          'remote-*',
          'rerere',
          'rev-list',
          'rev-parse',
          'runstatus',
          'sh-setup',
          'shell',
          'show-ref',
          'send-pack',
          'show-index',
          'ssh-*',
          'stripspace',
          'symbolic-ref',
          'unpack-file',
          'unpack-objects',
          'update-index',
          'update-ref',
          'update-server-info',
          'upload-archive',
          'upload-pack',
          'write-tree',
          'var',
          'verify-pack',
          'verify-tag',
        );
        
        $last_component = $command;
        $results = array();
        preg_match_all('/  ([a-zA-Z0-9-]+)/', $stdout, $matches);
        foreach ($matches[1] as $match) {
          $priority = 2000;
          foreach ($low_level as $ll) {
            if (strpos($ll, '*') !== false) {
              if (preg_match('/'.str_replace('*', '(.*)', $ll).'/', $match) === 1) {
                $priority = 1500;
              }
            } else if ($ll === $match) {
              $priority = 1500;
            }
          }
        
          $entry = $match;
          if (strlen($entry) >= strlen($last_component)) {
            if (substr($entry, 0, strlen($last_component)) === $last_component) {
              if ($current['original'] === $command) {
                $append = substr($entry, strlen($last_component));
                $append = str_replace(" ", "' '", $append); // TODO Make this nicer
                $results[] = array(
                  'append' => $append,
                  'node_replace' => $current['original'].$append,
                  'length' => strlen($current['original'].$append),
                  'description' => ($priority < 2000) ? 'git plumbing' : 'git subcommand',
                  'priority' => $priority,
                  'wrap_quotes' => !$safe_to_append,
                );
              }
            }
          }
        }
        
        return $results;
    }
    
    $results = array();
      
    if ($expects_branch) {
      list($stdout, $stderr) = id(new ExecFuture('git %Ls branch', $main_args))
        ->resolvex();
      
      foreach (phutil_split_lines($stdout) as $entry) {
        $entry = trim(substr($entry, 2));
        if (strlen($entry) >= strlen($last_component)) {
          if (substr($entry, 0, strlen($last_component)) === $last_component) {
            $append = substr($entry, strlen($last_component));
            $append = str_replace(" ", "' '", $append); // TODO Make this nicer
            $results[] = array(
              'append' => $append,
              'node_replace' => $current['original'].$append,
              'length' => strlen($current['original'].$append),
              'description' => 'branch in repository',
              'priority' => 2000,
              'wrap_quotes' => !$safe_to_append,
            );
          }
        }
      }
    }
      
    return $results;
  }

}