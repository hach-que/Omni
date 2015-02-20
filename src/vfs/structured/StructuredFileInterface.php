<?php

interface StructuredFileInterface {

  function getColoredFileName();
  
  function getFileName();
  
  function isSymbolicLink();
  
  function isDirectory();
  
  function getModificationTime();
  
  function getAccessTime();
  
  function getCreationTime();
  
  function getHardLinkCount();
  
  function getChildrenCount();
  
  function getMetaTarget();
  
  function getPermissionsCharacterString();
  
  function getOwnerName();
  
  function getGroupName();
  
  function getSize();

}