<?php

final class PipeCallVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    $arguments = $this->visitChild($shell, $data['children'][0]);
    
    $operation = 'new';
    $pipe = null;
    $distribution = null;
    $convert_type = null;
    
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
        case '-h':
        case '--help':
          $help = <<<EOF
pipe [options]

Creates or modifies pipes in Omni.  The default operation is
to create a new pipe.

  General options:
  
  -c                 Create a new pipe (default).
  -m pipe            Modify an existing pipe.
  
  Distribution options:
  
  -d method          Specifies how objects should be distributed to the
                     outbound streams.  The methods are as follows:
  
     split           The set of objects retrieved from the input streams are
                     evenly split amongst the output streams that can
                     accept them.

  Conversion options:

  -b
  --boolean          Specifies this pipe should convert values to booleans.
  -i
  --integer          Specifies this pipe should convert values to integers.
  -f
  --float            Specifies this pipe should convert values to floats.
  -s
  --string           Specifies this pipe should convert values to strings.
  -a
  --array            Specifies this pipe should convert values to arrays.
  -o
  --object           Specifies this pipe should convert values to generic objects.
  --resource         Specifies this pipe should convert values to resources.  Given
                     there's no way to convert resources, this serves only to assert
                     that a pipe's input objects are all resources.
  --null             Specifies this pipe should convert values to null.
  -t type            Specifies this pipe should convert values to the specified type.

EOF;
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
    
    return $pipe;
  }
  
}

    