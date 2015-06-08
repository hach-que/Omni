<?php

final class TestVirtualFSProvider extends VirtualFSProvider {

  public function getPrefix() {
    return 'test';
  }
  
  public function ls($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
  
    if ($path === '/') {
      return array(
        'ls-sort-map-does-not-deadlock'
      );
    } else if ($path === '/ls-sort-map-does-not-deadlock') {
      return array(
        'fileA',
        'fileB',
        'fileC',
      );
    }
  }
  
  public function exists($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    $exists = array(
      '/',
      '/ls-sort-map-does-not-deadlock',
      '/ls-sort-map-does-not-deadlock/fileA',
      '/ls-sort-map-does-not-deadlock/fileB',
      '/ls-sort-map-does-not-deadlock/fileC',
    );
    
    return in_array($path, $exists);
  }
  
  public function isDir($path) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    $dirs = array(
      '/',
      '/ls-sort-map-does-not-deadlock',
    );
    
    return in_array($path, $dirs);
  }
  
  public function getFileObject($path, $name) {
    $path = VirtualFS::resolveMetaPathComponents($path);
    
    $dirs = array(
      '/',
      '/ls-sort-map-does-not-deadlock',
    );
    
    if (in_array($path, $dirs)) {
      return new MetaDirectoryStructuredFile($path, $name);
    } else if ($path === '/ls-sort-map-does-not-deadlock/fileA') {
      return id(new TestStructuredFile('fileA', 'fileA'))
        ->setTestID(123456);
    } else if ($path === '/ls-sort-map-does-not-deadlock/fileB') {
      return id(new TestStructuredFile('fileB', 'fileB'))
        ->setTestID(345678);
    } else if ($path === '/ls-sort-map-does-not-deadlock/fileC') {
      return id(new TestStructuredFile('fileC', 'fileC'))
        ->setTestID(1000);
    }
    
    throw new Exception('No such file exists for '.$path);
  }

}