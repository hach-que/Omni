<?php

final class PipelineVisitor extends Visitor {
  
  protected function visitImpl(Shell $shell, array $data) {
    omni_trace("constructing pipeline");
    
    $pipeline = new Pipeline();
    $pipeline->setCommand($data['original']);
    for ($i = 0; $i < count($data['children']); $i++) {
      $child = $data['children'][$i];
      $pipeline->addStage($this->visitChild($shell, $child));
    }
    
    return $pipeline;
  }
  
}
