<?php

final class TestStructuredFile
  extends Phobject
  implements
    StructuredFileInterface {

  private $path;
  private $originalName;
  private $testID;
  
  public function __construct($path, $original_name = null) {
    $this->path = $path;
    $this->originalName = $original_name;
  }
  
  public function setTestID($id) {
    $this->testID = $id;
    return $this;
  }
  
  public function getTestID() {
    return $this->testID;
  }
  
  public function getColoredFileName() {
    return $this->getFileName();
  }
  
  public function getFileName() {
    return basename($this->path);
  }
  
  public function isSymbolicLink() {
    return false;
  }
  
  public function isDirectory() {
    return false;
  }
  
  public function getHardLinkCount() {
    return 1;
  }
  
  public function getChildrenCount() {
    return 0;
  }
  
  public function getMetaTarget() {
    return null;
  }
  
}