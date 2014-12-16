#!/usr/bin/env php
<?php
      
declare(ticks=1);

require_once dirname(__FILE__).'/__sanity_check__.php';

sanity_check_environment();

require_once dirname(__FILE__).'/__init_script__.php';

ini_set('memory_limit', -1);

$args = new PhutilArgumentParser($argv);
$args->parseStandardArguments();
$result = $args->parsePartial(
  array(
    array(
      'name'     => 'command',
      'wildcard' => true,
      'help'     => 'The command to execute.  If this '.
                    'is not specified, Omni runs as an '.
                    'interactive shell.',
    ),
    array(
      'name'     => 'ast-command',
      'param'    => 'ast',
      'help'     => 'Print the AST for the specified command.',
    ),
    array(
      'name'     => 'simulate-interactive',
      'help'     => 'Simulate commands being typed at the interactive prompt.',
    ),
    array(
      'name'     => 'raw',
      'help'     => 'Use raw mode when shell is interactive.',
    ),
));

if ($args->getArg('trace')) {
  // Enable tracing for both PHP and native extensions.
  OmniTrace::enableTracing();
}

$command = $args->getArg('command');

omni_trace("starting");

if ($args->getArg('ast-command')) {
  $result = omnilang_parse($args->getArg('ast-command'));
  print_r($result);
  if ($result === false) {
    omni_exit(1);
  } else {
    omni_exit(0);
  }
}

omni_trace("check if tty");

if (posix_isatty(Shell::STDIN_FILENO) && count($command) === 0) {
  omni_trace("starting interactive shell");

  // Run as an interactive shell.
  $tty = new InteractiveTTYEditline();
  if ($args->getArg('raw')) {
    $tty->setRaw(true);
  }
  if ($args->getArg('simulate-interactive')) {
    $tty->simulate();
  } else {
    $tty->run();
  }
} else {
  omni_trace("executing command");

  $shell = new Shell();
  $shell->executeFromArray($command);
}

omni_exit(0);