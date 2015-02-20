<?php

final class VirtualFSSession extends Phobject {

  private $vfs;
  private $cwd;
  
  public function __construct(VirtualFS $vfs, $path) {
    $this->vfs = $vfs;
    $this->cwd = rtrim($path, '/').'/';
  }
  
  public function changeDirectoryAbsolute($path) {
    $path = rtrim($path, '/').'/';
    list($prefix, $real_path) = VirtualFS::parsePath($path);
    if ($prefix === '') {
      $pass = !(!(@chdir($real_path)));
    } else {
      if (!$this->vfs->isDir($path)) {
        // Not a directory in the VFS, so fail to chdir.
        return false;
      }
      $pass = !(!(@chdir('/tmp')));
    }
    if (!$pass) {
      return false;
    }
    $this->cwd = VirtualFS::resolveMetaPathComponents($real_path);
    if ($prefix !== '') {
      $this->cwd = $prefix.':'.$this->cwd;
    }
    return true;
  }
  
  public function getCurrentDirectory() {
    return $this->cwd;
  }
  
  public function makeAbsolute($path) {
    omni_trace("vfs: makeAbsolute: start ".$path);
    
    if (strlen($path) >= 1) {
      if ($path[0] === '/') {
        omni_trace("vfs: makeAbsolute: detected absolute, returning as-is");
        return $path;
      }
    }
    
    $prefix_pos = strpos($path, ':');
    if ($prefix_pos === false) {
      omni_trace("vfs: makeAbsolute: no prefix detected, returning relative");
      return $this->cwd.$path;
    }
    
    $slash_pos = strpos($path, '/');
    if ($slash_pos === false) {
      if ($prefix_pos === strlen($path) - 1) {
        omni_trace("vfs: makeAbsolute: prefix is last char, returning absolute");
        return $path.'/';
      }
      
      omni_trace("vfs: makeAbsolute: no slash detected, returning relative");
      return $this->cwd.$path;
    }
    
    if ($prefix_pos !== $slash_pos - 1) {
      omni_trace("vfs: makeAbsolute: slash not immediately after prefix, returning relative");
      return $this->cwd.$path;
    }
    
    omni_trace("vfs: makeAbsolute: is absolute prefix, returning as-is");
    return $path;
  }
  
  public function ls($path) {
    $path = $this->makeAbsolute($path);
    omni_trace("vfs: ls ".$path);
    return $this->vfs->ls($path);
  }
  
  public function exists($path) {
    $path = $this->makeAbsolute($path);
    omni_trace("vfs: exists ".$path);
    return $this->vfs->exists($path);
  }
  
  public function isDir($path) {
    $path = $this->makeAbsolute($path);
    omni_trace("vfs: isDir ".$path);
    return $this->vfs->isDir($path);
  }
  
  public function getFileObject($path, $name) {
    $path = $this->makeAbsolute($path);
    omni_trace("vfs: getFileObject ".$path);
    return $this->vfs->getFileObject($path, $name);
  }

}