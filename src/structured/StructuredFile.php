<?php

final class StructuredFile extends Phobject {

  private $path;
  private $lstat;
  
  public function __construct($path) {
    $this->path = Filesystem::resolvePath($path);
    $this->lstat = lstat($this->path);
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
    return idx($this->lstat, 'mode');
  }
  
  public function getPermissionsString() {
    return substr(sprintf('%o', $this->getPermissions()), -4);
  }
  
  public function getCreationTime() {
    return idx($this->lstat, 'ctime');
  }
  
  public function getModificationTime() {
    return idx($this->lstat, 'mtime');
  }
  
  public function getAccessTime() {
    return idx($this->lstat, 'atime');
  }
  
  public function getOwnerID() {
    return idx($this->lstat, 'uid');
  }
  
  public function setOwnerID($id) {
    $result = @chown($this->path, $id);
    $this->lstat = lstat($this->path);
    return $result;
  }
  
  public function getOwnerName() {
    return idx(posix_getpwuid($this->getOwnerID()), 'name');
  }
  
  public function setOwnerName($name) {
    $user = posix_getpwnam($name);
    if ($user === false) {
      return false;
    }
    $uid = idx($user, 'uid');
    if ($uid === null) {
      return false;
    }
    
    $result = @chown($this->path, $uid);
    $this->lstat = lstat($this->path);
    return $result;
  }
  
  public function getGroupID() {
    return idx($this->lstat, 'gid');
  }
  
  public function setGroupID($id) {
    $result = @chgrp($this->path, $id);
    $this->lstat = lstat($this->path);
    return $result;
  }
  
  public function getGroupName() {
    return idx(posix_getgrgid($this->getGroupID()), 'name');
  }
  
  public function setGroupName($name) {
    $grp = posix_getgrnam($name);
    if ($grp === false) {
      return false;
    }
    $gid = idx($grp, 'gid');
    if ($gid === null) {
      return false;
    }
    
    $result = @chgrp($this->path, $gid);
    $this->lstat = lstat($this->path);
    return $result;
  }
  
  public function getDeviceNumber() {
    return idx($this->lstat, 'dev');
  }
  
  public function getDeviceType() {
    return idx($this->lstat, 'rdev');
  }
  
  public function getInodeNumber() {
    return idx($this->lstat, 'ino');
  }
  
  public function getHardLinkCount() {
    if ($this->isDirectory()) {
      return 1;
    } else {
      return idx($this->lstat, 'nlink');
    }
  }
  
  public function getChildrenCount() {
    if ($this->isDirectory()) {
      return idx($this->lstat, 'nlink') - 2;
    } else {
      return 0;
    }
  }
  
  public function getSize() {
    return idx($this->lstat, 'size');
  }
  
  public function getBlockSize() {
    return idx($this->lstat, 'blksize');
  }
  
  public function getBlocks() {
    return idx($this->lstat, 'blocks');
  }
  
  public function delete() {
    return @unlink($this->path);
  }

}