<?php

/**
 * Represents an abstract stream of incoming objects.
 *
 * Objects can be pulled from this stream using the read() method.
 */
abstract class InboundStream extends Phobject {
  
  abstract function getSourceType();
  
  abstract function canRead();
  
  /**
   * Reads all of the available objects from the stream, returning
   * an array of the read objects.
   */
  abstract function read();
  
  abstract function isExhausted();
  
}