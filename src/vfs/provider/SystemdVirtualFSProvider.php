<?php

final class SystemdVirtualFSProvider extends VirtualFSProvider {

  public function getPrefix() {
    return 'systemd';
  }
  
  public function ls($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
  
    if ($path === '/') {
      return array(
        'automount',
        'device',
        'mount',
        'path',
        'scope',
        'service',
        'slice',
        'socket',
        'swap',
        'target',
        'timer',
      );
    } else {
      $type = substr($path, 1);
      
      list($stdout, $stderr) = 
        id(new ExecFuture("systemctl list-units --all --type=%s", $type))
          ->resolvex();
      $lines = phutil_split_lines($stdout);
      $results = array();
      foreach ($lines as $line) {
        $components = explode(' ', $line);
        $name = $components[0];
        if ($name === 'UNIT') {
          continue;
        }
        if (trim($name) === '') {
          break;
        }
        $dotpos = strpos($name, '.');
        $results[] = substr($name, 0, $dotpos);
      }
      return $results;
    }
  }
  
  public function exists($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    return true;
  }
  
  public function isDir($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    $dirs = array(
      '/',
      '/automount',
      '/device',
      '/mount',
      '/path',
      '/scope',
      '/service',
      '/slice',
      '/socket',
      '/swap',
      '/target',
      '/timer',
    );
    
    return in_array($path, $dirs);
  }
  
  public function getFileObject($path, $name) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    $dirs = array(
      '/',
      '/automount',
      '/device',
      '/mount',
      '/path',
      '/scope',
      '/service',
      '/slice',
      '/socket',
      '/swap',
      '/target',
      '/timer',
    );
    
    if (in_array($path, $dirs)) {
      return new MetaDirectoryStructuredFile($path, $name);
    } else {
      return new SystemdUnitStructuredFile($path, $name);
    }
  }

}