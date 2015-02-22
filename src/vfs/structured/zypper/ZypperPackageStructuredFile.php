<?php

final class ZypperPackageStructuredFile
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
  
  /* ====== Package Methods ====== */
  
  public function install() {
    $cmd = "sudo zypper --non-interactive in %s";
    return id(new ExecFuture($cmd, $this->getFileName()))
      ->resolvex();
  }
  
  public function remove() {
    $cmd = "sudo zypper --non-interactive rm %s";
    return id(new ExecFuture($cmd, $this->getFileName()))
      ->resolvex();
  }
  
}