<?php

interface StructuredFilePermissionsInterface {
  
  function getPermissions();
  
  function getPermissionsOctalString();
  
  function getPermissionsCharacterString();
  
  function getOwnerID();
  
  function getOwnerName();
  
  function getGroupID();
  
  function getGroupName();
  
}