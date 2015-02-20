<?php

final class VFSProviderStructuredFile
  extends Phobject
  implements StructuredFileInterface {

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
    return $this->path === '/';
  }
  
  public function getModificationTime() {
    return 0;
  }
  
  public function getAccessTime() {
    return 0;
  }
  
  public function getCreationTime() {
    return 0;
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
  
  public function getPermissionsCharacterString() {
    return '';
  }
  
  public function getOwnerName() {
    return '';
  }
  
  public function getGroupName() {
    return '';
  }
  
  public function getSize() {
    return 0;
  }

}