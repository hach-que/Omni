<?php

interface StructuredFileInterface {

  function getColoredFileName();
  
  function getFileName();
  
  function isSymbolicLink();
  
  function isDirectory();
  
  function getHardLinkCount();
  
  function getChildrenCount();
  
  function getMetaTarget();

}