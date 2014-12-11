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
      'name'     => 'self-test',
      'help'     => 'Run Omni\'s self testing suite.'
    ),
));

$command = $args->getArg('command');
$self_test = $args->getArg('self-test');

if ($self_test) {
  $runner = new SelfTestRunner();
  $runner->run();
  exit(0); // Tests will run exit(1) if they fail, so if we get to here we've passed.
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