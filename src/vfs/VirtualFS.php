<?php

final class VirtualFS extends Phobject {

  private $providers;

  public function __construct() {
    $this->providers = id(new PhutilSymbolLoader())
      ->setAncestorClass('VirtualFSProvider')
      ->loadObjects();
    $this->providers = mpull($this->providers, null, 'getPrefix');
  }
  
  public static function parsePath($path) {
    if (!is_string($path)) {
      throw new Exception('Expected string path, got '.print_r($path, true));
    }
  
    if (strlen($path) >= 1) {
      if ($path[0] === '/') {
        return array('', $path);
      }
    }
    
    $prefix_pos = strpos($path, ':');
    if ($prefix_pos === false) {
      throw new Exception('Invalid path specification "'.$path.'"');
    }
    
    $prefix = substr($path, 0, $prefix_pos);
    $path = substr($path, $prefix_pos + 1);
    return array($prefix, $path);
  }
  
  public static function resolveMetaPathComponents($path) {
    $starts_with_slash = strlen($path) >= 1 && $path[0] === '/';
    $components = explode('/', $path);
    $result = array();
    for ($i = 0; $i < count($components); $i++) {
      if ($components[$i] === '.') {
        continue;
      } else if ($components[$i] === '..') {
        if (count($result) > 0) {
          array_pop($result);
        }
      } else {
        $result[] = $components[$i];
      }
    }
    $result = implode('/', $result);
    if ($starts_with_slash) {
      $result = '/'.ltrim($result, '/');
    }
    return $result;
  }
  
  private function getProviderAndPath($path) {
    list($prefix, $path) = self::parsePath($path);
    omni_trace("vfs: prefix is $prefix, path is $path");
    $provider = idx($this->providers, $prefix);
    if ($provider === null) {
      throw new Exception(
        'No virtual FS provider for \''.$prefix.'\' prefix.'); 
    }
    return array($provider, $path);
  }

  public function ls($path) {
    list($provider, $path) = $this->getProviderAndPath($path);
    return $provider->ls($path);
  }

  public function exists($path) {
    list($provider, $path) = $this->getProviderAndPath($path);
    return $provider->exists($path);
  }

  public function isDir($path) {
    list($provider, $path) = $this->getProviderAndPath($path);
    return $provider->isDir($path);
  }

  public function getFileObject($path, $name) {
    list($provider, $path) = $this->getProviderAndPath($path);
    return $provider->getFileObject($path, $name);
  }

}