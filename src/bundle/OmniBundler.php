<?php

final class OmniBundler extends Phobject {

  private function findClass(
    array $maps,
    $class_to_include) {
    
    foreach ($maps as $library => $map) {
      if (idx($map['class'], $class_to_include) !== null) {
        return $library;
      }
    }
    
    if (class_exists($class_to_include, false) || interface_exists($class_to_include, false)) {
      return true;
    }
    
    return null;
  }

  private function includeClass(
    PhutilBallOfPHP $bootstrap,
    array $maps,
    &$already_included,
    $class_to_include) {
    
    $library = $this->findClass($maps, $class_to_include);
    if ($library === null) {
      omni_trace("class not found $class_to_include");
      return false;
    } else if ($library === true) {
      // Builtin
      return true;
    }
    
    $file = $maps[$library]['class'][$class_to_include];
    
    if (idx($already_included, $library.' -- '.$file, false)) {
      return true;
    }
    
    $parents = idx($maps[$library]['xmap'], $class_to_include);
    if (is_string($parents)) {
      if (!$this->includeClass($bootstrap, $maps, $already_included, $parents)) {
        omni_trace("can not include $parents");
        return false;
      }
    } else if (is_array($parents)) {
      foreach ($parents as $parent) {
        if (!$this->includeClass($bootstrap, $maps, $already_included, $parent)) {
          omni_trace("can not include $parent");
          return false;
        }
      }
    }
    
    if (idx($already_included, $library.' -- '.$file, false)) {
      return true;
    }
    
    $root = phutil_get_library_root($library);
    $bootstrap->addFile($root.'/'.$file);
    $already_included[$library.' -- '.$file] = true;
    omni_trace("added ($library) $file");
    
    return true;
  }

  public function bundle($script_path) {
    Filesystem::assertExists($script_path);
  
    $script_contents = file_get_contents($script_path);
    $encoded_script_contents = base64_encode($script_contents);
    
    $bootstrap = new PhutilBallOfPHP();
    $libraries = array('phutil', 'omni');
    $maps = array();
    foreach ($libraries as $library) {
      $root = phutil_get_library_root($library);
      $maps[$library] = PhutilBootloader::getInstance()->getLibraryMap($library);
    }
    
    $already_included = array();
    
    foreach ($maps as $library => $map) {
      foreach ($map['class'] as $class => $file) {
        if (substr_count($file, "__tests__") > 0) {
          continue;
        }
        
        $this->includeClass(
          $bootstrap,
          $maps,
          $already_included,
          $class);
      }
      
      foreach ($map['function'] as $function => $file) {
        if (substr_count($file, "__tests__") > 0) {
          continue;
        }
        
        if (idx($already_included, $library.' -- '.$file, false)) {
          continue;
        }
        
        $root = phutil_get_library_root($library);
        $bootstrap->addFile($root.'/'.$file);
        $already_included[$library.' -- '.$file] = true;
        omni_trace("added ($library) $file");
      }
    }
    
    $bootstrap->addText(<<<EOF
    
define('__LIBPHUTIL__', true);

EOF
    );
    
    $root = phutil_get_library_root('omni');
    $bootstrap->addFile($root.'/../scripts/__sanity_check__.php');
    
    foreach ($maps as $library => $map) {
      $library_serialized = base64_encode(serialize($library));
      $map_serialized = base64_encode(serialize($map));
      $bootstrap->addText(<<<EOF

PhutilBootloader::getInstance()->registerInMemoryLibrary(
  unserialize(base64_decode("$library_serialized")),
  unserialize(base64_decode("$map_serialized")));

EOF
      );
    }
    
    $execute_script = <<<EOF
    
declare(ticks=1);
sanity_check_environment();
ini_set('memory_limit', -1);

\$args = new PhutilArgumentParser(\$argv);
\$args->parseStandardArguments();
\$result = \$args->parsePartial(
  array(
    array(
      'name'     => 'command',
      'wildcard' => true,
      'help'     => 'The command to execute.  If this '.
                    'is not specified, Omni runs as an '.
                    'interactive shell.',
    ),
));

if (\$args->getArg('trace')) {
  // Enable tracing for both PHP and native extensions.
  OmniTrace::enableTracing();
}

\$command = \$args->getArg('command');

omni_trace("starting from bundle");

\$script = base64_decode("$encoded_script_contents");

\$stdin = id(new Endpoint(array('read' => FileDescriptorManager::STDIN_FILENO, 'write' => null)))
  ->setName("stdin")
  ->setReadFormat(Endpoint::FORMAT_BYTE_STREAM)
  ->setClosable(false);
\$stdout = id(new Endpoint(array('read' => null, 'write' => FileDescriptorManager::STDOUT_FILENO)))
  ->setName("stdin")
  ->setWriteFormat(Endpoint::FORMAT_USER_FRIENDLY)
  ->setClosable(false);
\$stderr = id(new Endpoint(array('read' => null, 'write' => FileDescriptorManager::STDERR_FILENO)))
  ->setName("stdin")
  ->setWriteFormat(Endpoint::FORMAT_USER_FRIENDLY)
  ->setClosable(false);

\$shell = new Shell();
\$shell->launchScriptFromText(
  new Job() /* default job is not used */,
  \$script,
  \$command,
  \$stdin,
  \$stdout,
  \$stderr);
EOF;
    
    $bootstrap->addText($execute_script);
    echo "#!/usr/bin/env php\n";
    echo "<?php\n";
    echo $bootstrap->toString();
    
    return 0;
  }

}