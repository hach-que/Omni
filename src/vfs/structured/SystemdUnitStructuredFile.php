<?php

final class SystemdUnitStructuredFile
  extends Phobject
  implements
    StructuredFileInterface,
    StructuredFileCustomInterface {

  private $path;
  private $originalName;
  private $cachedInfo = null;
  private $cachedInfoTimeout;
  
  public function __construct($path, $original_name = null) {
    $this->path = $path;
    $this->originalName = $original_name;
  }
  
  private function getUnitName() {
    $components = explode('/', ltrim($this->path, '/'));
    return $components[1].'.'.$components[0];
  }
  
  private function loadInfo() {
    if ($this->cachedInfo === null || time() > $this->cachedInfoTimeout) {
      $cmd = "systemctl show -- %s";
      list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
        ->resolvex();
    
      $data = array();
      $lines = phutil_split_lines($stdout);
      foreach ($lines as $line) {
        $equal = strpos($line, '=');
        $name = substr($line, 0, $equal);
        $value = substr($line, $equal + 1);
        $data[$name] = rtrim($value);
      }
     
      $this->cachedInfo = $data;
      $this->cachedInfoTimeout = time() + 60; // Cache for 1 minute.
    }
    
    return $this->cachedInfo;
  }
  
  public function getColoredFileName() {
    $this->loadInfo();
    
    $loaded = idx($this->cachedInfo, 'LoadState', 'unknown');
    $active = idx($this->cachedInfo, 'ActiveState', 'unknown');
    $prefix = '';
    if ($loaded === 'error' || $active === 'failed') {
      $prefix = "\x1B[31;1m";
    } else if ($active === 'active') {
      $prefix = "\x1B[32;1m";
    }
    
    return $prefix.$this->getFileName()."\x1B[0m";
  }
  
  public function getFileName() {
    return basename($this->path);
  }
  
  public function isSymbolicLink() {
    return false;
  }
  
  public function isDirectory() {
    return $this->path === '/';
  }
  
  public function getHardLinkCount() {
    return 0;
  }
  
  public function getChildrenCount() {
    return 0;
  }
  
  public function getMetaTarget() {
    return null;
  }
  
  public function getColumns() {
    return array(
      'loaded' => array('title' => 'Loaded'),
      'active' => array('title' => 'Active'),
      'status' => array('title' => 'Status'),
    );
  }
  
  public function getData() {
    $this->loadInfo();
    $data = $this->cachedInfo;
    return array(
      'loaded' => idx($data, 'LoadState', 'unknown'),
      'active' => idx($data, 'ActiveState', 'unknown'),
      'status' => idx($data, 'SubState', 'unknown'),
    );
  }

  /* ====== Systemd Unit Properties ====== */
  
  public function getDescription() {
    $this->loadInfo();
    return idx($this->cachedInfo, 'Description');
  }

  public function getName() {
    $this->loadInfo();
    return idx($this->cachedInfo, 'Name');
  }

  public function getLoadState() {
    $this->loadInfo();
    return idx($this->cachedInfo, 'LoadState');
  }

  public function getActiveState() {
    $this->loadInfo();
    return idx($this->cachedInfo, 'ActiveState');
  }

  public function getSubState() {
    $this->loadInfo();
    return idx($this->cachedInfo, 'SubState');
  }

  public function getType() {
    $this->loadInfo();
    return idx($this->cachedInfo, 'Type');
  }
  
  /* ====== Systemd Unit Methods ====== */
  
  public function start() {
    $cmd = "systemctl start -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  public function stop() {
    $cmd = "systemctl stop -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  public function reload() {
    $cmd = "systemctl reload -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  public function restart() {
    $cmd = "systemctl restart -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  public function tryRestart() {
    $cmd = "systemctl try-restart -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  public function reloadOrRestart() {
    $cmd = "systemctl reload-or-restart -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  public function reloadOrTryRestart() {
    $cmd = "systemctl reload-or-try-restart -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  public function isolate() {
    $cmd = "systemctl isolate -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  public function kill() {
    $cmd = "systemctl kill -- %s";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $this->cachedInfo = null;
  }
  
  /* ====== Journal Access ====== */
  
  public function loadLogs() {
    $cmd = "journalctl --unit=%s -o json";
    list($stdout, $stderr) = id(new ExecFuture($cmd, $this->getUnitName()))
      ->resolvex();
    $logs = array();
    $lines = phutil_split_lines($stdout);
    foreach ($lines as $line) {
      $logs[] = phutil_json_decode($line);
    }
    return $logs;
  }
  
}