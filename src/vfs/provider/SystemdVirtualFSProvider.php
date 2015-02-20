<?php

final class SystemdVirtualFSProvider extends VirtualFSProvider {

  public function getPrefix() {
    return 'system';
  }
  
  public function ls($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
  
    list($stdout, $stderr) = 
      id(new ExecFuture("systemctl list-units --all"))
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
      $results[] = $name;
    }
    return $results;
  }
  
  public function exists($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    return true;
  }
  
  public function isDir($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    return $path === '/';
  }
  
  public function getFileObject($path, $name) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    return new SystemdUnitStructuredFile($path, $name);
  }

}