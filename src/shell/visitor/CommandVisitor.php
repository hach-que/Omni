<?php

final class CommandVisitor extends Visitor {
  
  public function visit(Shell $shell, array $data) {
    $arguments = $this->visitChild($shell, $data['children'][0]);
    
    $executable = array_shift($arguments);
    $executable = Filesystem::resolveBinary($executable);
    array_unshift($arguments, $executable);
  
    $process = new Process();
    $process->argv = $arguments;
    return $process;
  }
  
}

    