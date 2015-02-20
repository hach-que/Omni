<?php

abstract class VirtualFSProvider extends Phobject {

  abstract function getPrefix();
  
  abstract function ls($path);
  
  abstract function exists($path);
  
  abstract function isDir($path);
  
  abstract function getFileObject($path, $name);

}