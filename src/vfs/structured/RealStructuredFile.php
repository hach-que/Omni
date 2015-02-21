<?php

final class RealStructuredFile
  extends Phobject
  implements
    StructuredFileInterface,
    StructuredFileTimeInterface,
    StructuredFilePermissionsInterface,
    StructuredFileSizeInterface {

  private $path;
  private $originalName;
  
  public function __construct($path, $original_name = null) {
    $this->path = Filesystem::resolvePath($path);
    $this->originalName = $original_name;
  }
  
  public function getColoredFileName() {
    $prefix = '';
    if (!$this->getLinkTargetExists()) {
      $prefix = "\x1B[33;1m\x1B[41;1m";
    } else if ($this->isSymbolicLink()) {
      $prefix = "\x1B[36;1m";
    } else if ($this->isDirectory()) {
      $prefix = "\x1B[34;1m";
    } else if ($this->isExecutableFile()) {
      $prefix = "\x1B[32m";
    } else {
      $prefix = "\x1B[37m";
    }
    
    return $prefix.$this->getFileName()."\x1B[0m";
  }
  
  public function getAbsolutePath() {
    return $this->path;
  }
  
  public function getReadablePath() {
    return Filesystem::readablePath($this->path);
  }
  
  public function getFileName() {
    if ($this->originalName) {
      return $this->originalName;
    }
    
    return basename($this->path);
  }
  
  public function getLinkTarget() {
    if ($this->isSymbolicLink()) {
      return readlink($this->path);
    }
    
    return null;
  }
  
  public function getLinkTargetExists() {
    if ($this->isSymbolicLink()) {
      $link = $this->getLinkTarget();
      if ($link[0] === '/') {
        return Filesystem::pathExists($link);
      } else {
        return Filesystem::pathExists($this->getParentDirectory()."/".$link);
      }
    }
    
    return $this->getExists();
  }
  
  public function getMetaTarget() {
    // Return real file name for "." and ".." entries.
    if ($this->originalName !== basename($this->path)) {
      return new RealStructuredFile($this->path);
    }
    
    if ($this->isSymbolicLink()) {
      $link = $this->getLinkTarget();
      if ($link[0] === '/') {
        return new RealStructuredFile($link, $link);
      } else {
        return new RealStructuredFile($this->getParentDirectory()."/".$link, $link);
      }
    }
    
    return null;
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
  
  public function getPermissionsOctalString() {
    if (!$this->getExists()) {
      return null;
    }
  
    return substr(sprintf('%o', $this->getPermissions()), -4);
  }
  
  public function getPermissionsCharacterString() {
    if (!$this->getExists()) {
      return null;
    }
    
    $user_read = 0400;
    $user_write = 0200;
    $user_execute = 0100;
    $group_read = 0040;
    $group_write = 0020;
    $group_execute = 0010;
    $other_read = 0004;
    $other_write = 0002;
    $other_execute = 0001;
    $setuid = 04000;
    $setgid = 02000;
    $sticky = 01000;
    
    $perms = $this->getPermissions();
    $buffer = '';
    $buffer .= ($perms & $user_read) ? 'r' : '-';
    $buffer .= ($perms & $user_write) ? 'w' : '-';
    $buffer .= ($perms & $user_execute) ? 'x' : '-';
    $buffer .= ($perms & $group_read) ? 'r' : '-';
    $buffer .= ($perms & $group_write) ? 'w' : '-';
    $buffer .= ($perms & $group_execute) ? 'x' : '-';
    $buffer .= ($perms & $other_read) ? 'r' : '-';
    $buffer .= ($perms & $other_write) ? 'w' : '-';
    $buffer .= ($perms & $other_execute) ? 'x' : '-';
    
    if ($perms & $setuid) {
      $buffer[2] = ($perms & $user_execute) ? 's' : 'S';
    }
    
    if ($perms & $setgid) {
      $buffer[5] = ($perms & $group_execute) ? 's' : 'S';
    }
    
    if ($perms & $sticky) {
      $buffer[8] = ($perms & $other_execute) ? 't' : 'T';
    }
    
    return $buffer;
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