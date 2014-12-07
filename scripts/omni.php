#!/usr/bin/env php
<?php

require_once dirname(__FILE__).'/__sanity_check__.php';

sanity_check_environment();

require_once dirname(__FILE__).'/__init_script__.php';

ini_set('memory_limit', -1);

$shell = new Shell();
$shell->run();

/*

$args = new PhutilArgumentParser($argv);
$args->parseStandardArguments();
$args->parsePartial(
  array(
    array(
      'name'    => 'command',
      'short'   => 'c',
      'param'   => 'command',
      'help'    => 'Run a command.',
    ),
));

$command = $args->getArg('command');

$interactive = ($command === null);
$console = PhutilConsole::getConsole();

if ($interactive) {
  // TODO: Implement interactive mode.
  $console->writeErr('Interactive mode is not yet supported.'."\n");
  exit(1);
} else {
  $parser = new CommandParser();
  
  $components = $parser->parse($command);
  
  if (count($components) === 0) {
    exit(1);
  }
  
  $exec = array_shift($components);
  
  if (!Filesystem::pathExists($exec)) {
    // We have to attempt to resolve it via PATH.
    $path = explode(':', getenv('PATH'));
    foreach ($path as $p) {
      $full_path = $p.'/'.$exec;
      if (Filesystem::pathExists($full_path)) {
        $exec = $full_path;
        break;
      }
    }
  }
  
  if (!Filesystem::pathExists($exec)) {
    $console->writeErr('Unable to locate \''.$exec.'\''."\n");
    exit(1);
  }
  
  $pid = pcntl_fork();
  if ($pid === -1) {
    $console->writeErr('Unable to start specified command'."\n");
    exit(1);
  } else if ($pid) {
    pcntl_wait($status);
    exit(pcntl_wexitstatus($status));
  } else {
    pcntl_exec(
      $exec,
      $components);
    
    // We never return from here, because pcntl_exec
    // replaces the current process.
  }
} 

*/