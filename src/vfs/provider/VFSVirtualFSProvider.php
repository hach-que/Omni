<?php

final class VFSVirtualFSProvider extends VirtualFSProvider {

  public function getPrefix() {
    return 'vfs';
  }
  
  public function ls($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
  
    if ($path === '/') {
      $providers = id(new PhutilSymbolLoader())
        ->setAncestorClass('VirtualFSProvider')
        ->loadObjects();
      $prefixes = mpull($providers, 'getPrefix');
      $results = array();
      foreach ($prefixes as $prefix) {
        if ($prefix !== '') {
          $results[] = $prefix;
        }
      }
      return $results;
    } else {
      $components = explode('/', ltrim($path, '/'));
      $dir = array_shift($components);
      $trailing = implode('/', $components);
      $vfs = new VirtualFS();
      return $vfs->ls($dir.':/'.$trailing);
    }
  }
  
  public function exists($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    if ($path === '/') {
      return true;
    } else {
      $components = explode('/', ltrim($path, '/'));
      $dir = array_shift($components);
      $trailing = implode('/', $components);
      $vfs = new VirtualFS();
      return $vfs->exists($dir.':/'.$trailing);
    }
  }
  
  public function isDir($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    if ($path === '/') {
      return true;
    } else {
      $components = explode('/', ltrim($path, '/'));
      $dir = array_shift($components);
      $trailing = implode('/', $components);
      $vfs = new VirtualFS();
      return $vfs->isDir($dir.':/'.$trailing);
    }
  }
  
  public function getFileObject($path, $name) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    if ($path === '/') {
      return new VFSProviderStructuredFile($path, $name);
    } elseif (substr_count($path, '/') === 1) {
      return new VFSProviderStructuredFile($path, $name);
    } else {
      $components = explode('/', ltrim($path, '/'));
      $dir = array_shift($components);
      $trailing = implode('/', $components);
      $vfs = new VirtualFS();
      return $vfs->getFileObject($dir.':/'.$trailing, $name);
    }
  }

}