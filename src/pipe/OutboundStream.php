<?php

/**
 * Represents an abstract stream of outgoing objects.
 *
 * Objects can be pushed to this stream using the write() method.
 */
abstract class OutboundStream extends Phobject {
  
  abstract function getTargetType();
  
  abstract function canWrite();
  
  /**
   * Writes the objects to stream; the objects themselves are written,
   * not the array.
   */
  abstract function write(array $objects);
  
}