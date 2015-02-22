<?php

final class ZypperVirtualFSProvider extends VirtualFSProvider {

  public function getPrefix() {
    return 'zypper';
  }
  
  public function ls($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
  
    if ($path === '/') {
      return array(
        'manager',
        'repos',
        'installed',
        'available',
      );
    } else {
      $type = substr($path, 1);
      
      switch ($type) {
        case 'repos':
        {
          list($stdout, $stderr) = 
            id(new ExecFuture("zypper -n --no-refresh lr -n"))
              ->resolvex();
          $lines = phutil_split_lines($stdout);
          $results = array();
          $name_pos = null;
          foreach ($lines as $line) {
            if (strlen($line) === 0) {
              continue;
            }
            
            if ($line[0] === '#') {
              $name_pos = strpos($line, 'Name');
            } else if ($line[1] === '-') {
              continue;
            } else if ($name_pos !== null) {
              $name_plus_extra = substr($line, $name_pos);
              $name_end = strpos($name_plus_extra, '|');
              $name = trim(substr($name_plus_extra, 0, $name_end));
              $results[] = $name;
            }
          }
          return $results;
        }
        case 'installed':
        case 'available':
        {
          $mode = null;
          if ($type === 'installed') {
            $mode = '-i';
          } else {
            $mode = '-u';
          }
          list($stdout, $stderr) = 
            id(new ExecFuture("zypper -n --no-refresh se %s", $mode))
              ->resolvex();
          $lines = phutil_split_lines($stdout);
          $results = array();
          $name_pos = null;
          foreach ($lines as $line) {
            if (strlen($line) === 0) {
              continue;
            }
            
            if ($line[0] === 'S') {
              $name_pos = strpos($line, 'Name');
            } else if ($line[0] === '-') {
              continue;
            } else if ($name_pos !== null) {
              $name_plus_extra = substr($line, $name_pos);
              $name_end = strpos($name_plus_extra, '|');
              $name = trim(substr($name_plus_extra, 0, $name_end));
              $results[] = $name;
            }
          }
          return $results;
        }
      }
      
      throw new Exception('ls called on unexpected path');
    }
  }
  
  public function exists($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    $dirs = array(
      '/',
      '/repos',
      '/installed',
      '/available',
    );
    
    if (in_array($path, $dirs)) {
      return true;
    } else if ($path === '/manager') {
      return true;
    } else if (substr($path, 0, strlen('/repos/')) === '/repos/') {
      $results = $this->ls('/repos');
      return in_array(substr($path, strlen('/repos/')), $results);
    } else if (substr($path, 0, strlen('/installed/')) === '/installed/') {
      $results = $this->ls('/installed');
      return in_array(substr($path, strlen('/installed/')), $results);
    } else if (substr($path, 0, strlen('/available/')) === '/available/') {
      $results = $this->ls('/available');
      return in_array(substr($path, strlen('/available/')), $results);
    }
    
    return false;
  }
  
  public function isDir($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    $dirs = array(
      '/',
      '/repos',
      '/installed',
      '/available',
    );
    
    return in_array($path, $dirs);
  }
  
  public function getFileObject($path, $name) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    $dirs = array(
      '/',
      '/repos',
      '/installed',
      '/available',
    );
    
    if (in_array($path, $dirs)) {
      return new MetaDirectoryStructuredFile($path, $name);
    } else if ($path === '/manager') {
      return new ZypperManagerStructuredFile($path, $name);
    } else if (substr($path, 0, strlen('/repos/')) === '/repos/') {
      return new ZypperRepositoryStructuredFile($path, $name);
    } else if (substr($path, 0, strlen('/installed/')) === '/installed/') {
      return new ZypperPackageStructuredFile($path, $name);
    } else if (substr($path, 0, strlen('/available/')) === '/available/') {
      return new ZypperPackageStructuredFile($path, $name);
    }
    
    throw new Exception('No such file exists for '.$path);
  }

}