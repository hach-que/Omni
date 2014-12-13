#!/usr/bin/env php
<?php

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
));

$command = $args->getArg('command');

if ($args->getArg('ast-command')) {
  $result = omnilang_parse($args->getArg('ast-command'));
  print_r($result);
  if ($result === false) {
    exit(1);
  } else {
    exit(0);
  }
}

if (posix_isatty(Shell::STDIN_FILENO) && count($command) === 0) {
  // Run as an interactive shell.
  $tty = new InteractiveTTYEditline();
  $tty->run();
} else {
  $shell = new Shell();
  $shell->executeFromArray($command);
}

exit(0);