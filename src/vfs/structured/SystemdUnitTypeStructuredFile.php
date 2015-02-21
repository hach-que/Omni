<?php

final class SystemdUnitTypeStructuredFile
  extends Phobject
  implements
    StructuredFileInterface {

  private $path;
  private $originalName;
  
  public function __construct($path, $original_name = null) {
    $this->path = $path;
    $this->originalName = $original_name;
  }
  
  public function getColoredFileName() {
    return "\x1B[34;1m".$this->getFileName()."\x1B[0m";
  }
  
  public function getFileName() {
    return basename($this->path);
  }
  
  public function isSymbolicLink() {
    return false;
  }
  
  public function isDirectory() {
    return true;
  }
  
  public function getHardLinkCount() {
    return 0;
  }
  
  public function getChildrenCount() {
    return 0;
  }
  
  public function getMetaTarget() {
    return null;
  }

}