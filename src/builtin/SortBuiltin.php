<?php

final class SortBuiltin extends Builtin {

  public function getName() {
    return 'sort';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    $stdin_endpoint = $stdin->createOutboundEndpoint(null, "sort stdin");
    $stdout_endpoint = $stdout->createInboundEndpoint(null, "sort stdout");
    
    return array(
      'stdin' => $stdin_endpoint,
      'stdout' => $stdout_endpoint,
      'close_read_on_fork' => array(
        $stdin_endpoint,
      ),
      'close_write_on_fork' => array(
        $stdout_endpoint,
      ),
    );
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(
      array(
        'name' => 'sort',
        'param' => 'func',
        'short' => 'f',
        'help' => 
          'The function which returns the object '.
          'or property to sort on.  If this argument '.
          'is not provided, then the original object '.
          'is used as the sort value.'
      ),
      array(
        'name' => 'desc',
        'short' => 'd',
        'help' => 
          'Reverse the result of the sort, so that '.
          'the output is in descending order.'
      ),
    );
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdin = idx($prepare_data, 'stdin');
    $stdout = idx($prepare_data, 'stdout');
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull($this->getArguments($shell, $job, $prepare_data));
    
    $sort_function = $parser->getArg('sort');
    
    if ($sort_function !== null && (!$sort_function instanceof OmniFunction)) {
      $stdin->close();
      $stdout->close();
      throw new Exception("The parameter --sort needs to be an Omni function.");
    }
    
    $objects = array();
    
    while (true) {
      try {
        $object = $stdin->read();
        
        if ($sort_function === null) {
          $objects[] = array(
            'index' => $object,
            'value' => $object
          );
        } else {
          $objects[] = array(
            'index' => $sort_function->callIterator($shell, array(), $object),
            'value' => $object
          );
        }
      } catch (NativePipeClosedException $ex) {
        break;
      }
    }
    
    $stdin->close();
    
    $objects = isort($objects, 'index');
    $objects = ipull($objects, 'value');
    
    if ($parser->getArg('desc')) {
      $objects = array_reverse($objects);
    }
    
    foreach ($objects as $obj) {
      $stdout->write($obj);
    }
    
    $stdout->closeWrite();
  }

}