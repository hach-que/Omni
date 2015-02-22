<?php

final class ZypperRepositoryStructuredFile
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
    return 0;
  }
  
  public function getChildrenCount() {
    return 0;
  }
  
  public function getMetaTarget() {
    return null;
  }
  
  /* ====== Repository Methods ====== */
  
  public function refresh() {
    $cmd = "sudo zypper --non-interactive ref -r %s";
    return id(new ExecFuture($cmd, $this->getFileName()))
      ->resolvex();
  }
  
}