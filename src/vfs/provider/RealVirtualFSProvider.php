<?php

final class RealVirtualFSProvider extends VirtualFSProvider {

  public function getPrefix() {
    return '';
  }
  
  public function ls($path) {
    return @Filesystem::listDirectory($path);
  }
  
  public function exists($path) {
    return @Filesystem::pathExists($path);
  }
  
  public function isDir($path) {
    return is_dir($path);
  }
  
  public function getFileObject($path, $name) {
    return new RealStructuredFile($path, $name);
  }

}