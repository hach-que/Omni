#!/usr/bin/env php
<?php
      
declare(ticks=1);

require_once dirname(__FILE__).'/__sanity_check__.php';

sanity_check_environment();

require_once dirname(__FILE__).'/__init_script__.php';

$provider = new ExtensionProvider();
$extension_paths = $provider->loadOrBuild(
  array(
    'editline',
    'tc',
    'fd',
    'omni',
    'omnilang',
  )
);

// Re-exec this PHP process if we weren't started with our PHP
// configuration file.
$template_php_ini = Filesystem::resolvePath(
  phutil_get_library_root('omni').'/../conf/php.ini.template');
$target_php_ini = Filesystem::resolvePath(
  phutil_get_library_root('omni').'/../conf/php.ini');
$write_out_php_ini = false;
if (!file_exists($target_php_ini)) {
  $write_out_php_ini = true;
} else {
  $contents = file_get_contents($target_php_ini);
  foreach ($extension_paths as $path) {
    if (substr_count($contents, "extension=$path") === 0) {
      $write_out_php_ini = true;
    }
  }
}
if ($write_out_php_ini) {
  $contents = file_get_contents($template_php_ini);
  $extension_decls = '';
  foreach ($extension_paths as $path) {
    $extension_decls .= "extension=$path\n";
  }
  $contents = str_replace('{EXTENSION_PATHS}', $extension_decls, $contents);
  file_put_contents($target_php_ini, $contents);
} 
if (php_ini_loaded_file() !== $target_php_ini) {
  $new_args = array(
    '-c',
    $target_php_ini,
    '-f',
    phutil_get_library_root('omni').'/../bin/omni',
    '--',
  );
  foreach (array_slice($argv, 1) as $arg) {
    $new_args[] = $arg;
  }
  pcntl_exec(PHP_BINARY, $new_args);
}

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
      'name'     => 'ast-script',
      'param'    => 'ast',
      'help'     => 'Print the AST for the specified script file.',
    ),
    array(
      'name'     => 'ast-locate',
      'param'    => 'position',
      'help'     => 'Show the list of nodes present at the specified location in the AST command.',
    ),
    array(
      'name'     => 'suggest',
      'param'    => 'position',
      'help'     => 'Use the suggestion engine to show suggestions for the given input.',
    ),
    array(
      'name'     => 'lex-command',
      'param'    => 'lex',
      'help'     => 'Print the lexical structure for the specified command.',
    ),
    array(
      'name'     => 'lex-script',
      'param'    => 'lex',
      'help'     => 'Print the lexical structure for the specified script file.',
    ),
    array(
      'name'     => 'simulate-interactive',
      'help'     => 'Simulate commands being typed at the interactive prompt.',
    ),
    array(
      'name'     => 'raw',
      'help'     => 'Use raw mode when shell is interactive.',
    ),
    array(
      'name'     => 'bundle',
      'param'    => 'script',
      'help'     => 
        'Bundles the specified script with Omni, so that you can '.
        'run the script without Omni installed.',
    ),
));

if ($args->getArg('trace')) {
  // Enable tracing for both PHP and native extensions.
  OmniTrace::enableTracing();
}

$command = $args->getArg('command');

omni_trace("starting");

if ($args->getArg('bundle')) {
  omni_trace("bundling ".$args->getArg('bundle'));
  
  omni_exit(
    id(new OmniBundler())
      ->bundle($args->getArg('bundle')));
}

if ($args->getArg('ast-command')) {
  $result = omnilang_parse($args->getArg('ast-command'));
  if ($result === false) {
    echo omnilang_get_error()."\n";
    omni_exit(1);
  } else {
    if ($args->getArg('ast-locate') !== null) {
      $engine = new SuggestionEngine();
      print_r($engine->traverseToPosition($result, $args->getArg('ast-locate')));
    } else {
      print_r($result);
    }
    omni_exit(0);
  }
}

if ($args->getArg('suggest')) {
  $length = strlen($args->getArg('suggest'));
  if ($args->getArg('ast-locate') !== null) {
    $length = $args->getArg('ast-locate');
  }
  $engine = new SuggestionEngine();
  $suggestions = $engine->getSuggestions(
    new Shell(), 
    $args->getArg('suggest'),
    $length);
  print_r($suggestions);
  omni_exit(0);
}

if ($args->getArg('ast-script')) {
  $result = omnilang_parse(file_get_contents($args->getArg('ast-script')));
  print_r($result);
  if ($result === false) {
    echo omnilang_get_error()."\n";
    omni_exit(1);
  } else {
    omni_exit(0);
  }
}

if ($args->getArg('lex-command')) {
  $result = omnilang_lex($args->getArg('lex-command'));
  if ($result === false) {
    echo omnilang_get_error()."\n";
    omni_exit(1);
  } else {
    foreach ($result as $token) {
      echo $token."\n";
    }
    omni_exit(0);
  }
}

if ($args->getArg('lex-script')) {
  $result = omnilang_lex(file_get_contents($args->getArg('lex-script')));
  if ($result === false) {
    echo omnilang_get_error()."\n";
    omni_exit(1);
  } else {
    foreach ($result as $token) {
      echo $token."\n";
    }
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
