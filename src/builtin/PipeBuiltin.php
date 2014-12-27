<?php

final class PipeBuiltin extends Builtin {

  public function getName() {
    return 'pipe';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    Pipe $stdin,
    Pipe $stdout,
    Pipe $stderr) {
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(null, "pipe builtin stdout"),
    );
  }
  
  public function getDescription() {
    return <<<EOF
Creates or modifies pipes in Omni.  The default operation is
to create a new pipe.
EOF;
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(
      array(
        'name' => 'create',
        'short' => 'c',
        'help' => 'Create a new pipe (default).'
      ),
      array(
        'name' => 'modify',
        'short' => 'm',
        'param' => 'pipe',
        'help' => 'Modify an existing pipe.'
      ),
      array(
        'name' => 'fd',
        'param' => 'fd',
        'help' => 
          'Attach the file descriptor to the new or existing '.
          'pipe.  You can use the special values "stdin", '.
          '"stdout" and "stderr" to refer to file descriptors '.
          '0, 1 and 2 respectively.'
      ),
    ); 
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    
    /*
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull($this->getArguments($shell, $job, $prepare_data));
    
    $arguments = $this->visitChild($shell, $data['children'][0]);
    
    $operation = 'new';
    $pipe = null;
    $distribution = null;
    $convert_type = null;
    $target_fd = null;
    
    for ($i = 0; $i < count($arguments); $i++) {
      $arg = $arguments[$i];
      switch ($arg) {
        case '-c':         $operation = 'new'; break;
        case '-m':
          $operation = 'modify';
          $pipe = $arguments[++$i];
          break;
        case '-d':
          $distribution = $arguments[++$i];
          break;
        case '-b': 
        case '--boolean':  $convert_type = TypeConverter::TYPE_BOOLEAN; break;
        case '-i': 
        case '--integer':  $convert_type = TypeConverter::TYPE_INTEGER; break;
        case '-f': 
        case '--float':    $convert_type = TypeConverter::TYPE_FLOAT; break;
        case '-s': 
        case '--string':   $convert_type = TypeConverter::TYPE_STRING; break;
        case '-a': 
        case '--array':    $convert_type = TypeConverter::TYPE_ARRAY; break;
        case '-o': 
        case '--object':   $convert_type = TypeConverter::TYPE_OBJECT; break;
        case '--resource': $convert_type = TypeConverter::TYPE_RESOURCE; break;
        case '--null':     $convert_type = TypeConverter::TYPE_NULL; break;
        case '-t':
          $type = $arguments[++$i];
          $convert_type = $type; 
          break;
        case '--fd':
          $target_fd = $arguments[++$i];
          break;
        case '-h':
        case '--help':
          $help =
          echo $help;
      }
    }
    
    if ($operation === 'new') {
      $pipe = new Pipe();
    }
    
    if ($distribution !== null) {
      $pipe->setDistributionMethod($distribution);
    }
    
    if ($convert_type !== null) {
      $pipe->setTypeConversion($convert_type);
    }
    
    $stdout->write($pipe);
    
    */
    
    $stdout->closeWrite();
  }

}