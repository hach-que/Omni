<?php

final class StructuredFile extends Phobject {

  private $path;
  
  public function __construct($path) {
    $this->path = Filesystem::resolvePath($path);
  }
  
  public function getAbsolutePath() {
    return $this->path;
  }
  
  public function getReadablePath() {
    return Filesystem::readablePath($this->path);
  }
  
  public function getFileName() {
    return basename($this->path);
  }
  
  public function getParentDirectory() {
    return dirname($this->path);
  }
  
  public function getExists() {
    return Filesystem::pathExists($this->path);
  }
  
  public function isRegularFile() {
    return (bool)is_file($this->path);
  }
  
  public function isDirectory() {
    return (bool)is_dir($this->path);
  }
  
  public function isExecutableFile() {
    return (bool)(is_executable($this->path) && !is_dir($this->path));
  }
  
  public function isSymbolicLink() {
    return (bool)is_link($this->path);
  }
  
  public function isHidden() {
    $name = basename($this->path);
    return strlen($this->path) > 0 && $name[0] === '.';
  }
  
  public function getPermissions() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'mode');
  }
  
  public function getPermissionsString() {
    if (!$this->getExists()) {
      return null;
    }
  
    return substr(sprintf('%o', $this->getPermissions()), -4);
  }
  
  public function getCreationTime() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'ctime');
  }
  
  public function getModificationTime() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'mtime');
  }
  
  public function getAccessTime() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'atime');
  }
  
  public function getOwnerID() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'uid');
  }
  
  public function setOwnerID($id) {
    if (!$this->getExists()) {
      throw new Exception('setOwnerID can not be called on non-existant files');
    }
  
    $result = @chown($this->path, $id);
    return $result;
  }
  
  public function getOwnerName() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(posix_getpwuid($this->getOwnerID()), 'name');
  }
  
  public function setOwnerName($name) {
    if (!$this->getExists()) {
      throw new Exception('setOwnerName can not be called on non-existant files');
    }
  
    $user = posix_getpwnam($name);
    if ($user === false) {
      return false;
    }
    $uid = idx($user, 'uid');
    if ($uid === null) {
      return false;
    }
    
    $result = @chown($this->path, $uid);
    return $result;
  }
  
  public function getGroupID() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'gid');
  }
  
  public function setGroupID($id) {
    if (!$this->getExists()) {
      throw new Exception('setGroupID can not be called on non-existant files');
    }
  
    $result = @chgrp($this->path, $id);
    return $result;
  }
  
  public function getGroupName() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(posix_getgrgid($this->getGroupID()), 'name');
  }
  
  public function setGroupName($name) {
    if (!$this->getExists()) {
      throw new Exception('setGroupName can not be called on non-existant files');
    }
  
    $grp = posix_getgrnam($name);
    if ($grp === false) {
      return false;
    }
    $gid = idx($grp, 'gid');
    if ($gid === null) {
      return false;
    }
    
    $result = @chgrp($this->path, $gid);
    return $result;
  }
  
  public function getDeviceNumber() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'dev');
  }
  
  public function getDeviceType() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'rdev');
  }
  
  public function getInodeNumber() {
    if (!$this->getExists()) {
      return null;
    }
  
    return idx(lstat($this->path), 'ino');
  }
  
  public function getHardLinkCount() {
    if (!$this->getExists()) {
      return 0;
    }
  
    if ($this->isDirectory()) {
      return 1;
    } else {
      return idx(lstat($this->path), 'nlink');
    }
  }
  
  public function getChildrenCount() {
    if (!$this->getExists()) {
      return 0;
    }
  
    if ($this->isDirectory()) {
      return idx(lstat($this->path), 'nlink') - 2;
    } else {
      return 0;
    }
  }
  
  public function getSize() {
    if (!$this->getExists()) {
      return 0;
    }
  
    return idx(lstat($this->path), 'size');
  }
  
  public function getBlockSize() {
    if (!$this->getExists()) {
      return 0;
    }
  
    return idx(lstat($this->path), 'blksize');
  }
  
  public function getBlocks() {
    if (!$this->getExists()) {
      return 0;
    }
  
    return idx(lstat($this->path), 'blocks');
  }
  
  public function delete() {
    if (Filesystem::pathExists($this->path)) {
      return @unlink($this->path);
    }
  }
  
  public function createFile() {
    if (!Filesystem::pathExists($this->path)) {
      return @touch($this->path);
    }
    
    return true;
  }

  public function createDirectory() {
    if (!Filesystem::pathExists($this->path)) {
      return @mkdir($this->path);
    }
    
    return true;
  }
  
  public function loadContent() {
    if (!$this->getExists()) {
      return null;
    }
  
    return file_get_contents($this->path);
  }
  
  public function setContent($content) {
    if (!$this->getExists()) {
      $this->createFile();
    }
  
    file_put_contents($this->path, $content);
  }
  
}