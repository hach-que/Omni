<?php

final class IterBuiltin extends Builtin {
  
  public function getName() {
    return 'iter';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(null, "iter builtin stdout"),
    );
  }
  
  public function getDescription() {
    return <<<EOF
Iterates through a list of objects passed on the command line,
pushing each item to standard output individually.

Used in constructs such as:

  echo $(iter (\$obj->loadLogs()) | () => (\$_->MESSAGE))

EOF;
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(); 
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    
    for ($i = 1; $i < count($arguments); $i++) {
      $arg = $arguments[$i];
      if (is_array($arg)) {
        foreach ($arg as $a) {
          $stdout->write($a);
        }
      } else {
        $stdout->write($arg);
      }
    }
    
    $stdout->closeWrite();
  }

}