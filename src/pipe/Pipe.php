<?php

/**
 * An abstraction on top of the native system pipes, which allows multiple inputs
 * and outputs, and permits complex objects to be transmitted over the stream
 * (via serialization).
 */
final class Pipe extends Phobject {

  const DIST_METHOD_SPLIT = 'split';
  
  private $targetType = null;
  private $distributionMethod = self::DIST_METHOD_SPLIT;
  private $inboundStreams = array();
  private $outboundStreams = array();
  
  public function setTypeConversion($target_type) {
    $this->targetType = $target_type;
    return $this;
  }
  
  public function setDistributionMethod($dist_method) {
    $this->distributionMethod = $dist_method;
    return $this;
  }
  
  public function attachInbound(InboundStream $inbound) {
    $this->inboundStreams[] = $inbound;
  }
  
  public function attachOutbound(OutboundStream $outbound) {
    $this->outboundStreams[] = $outbound;
  }

  /**
   * Performs a single update step, taking objects from the
   * inbound streams and directing them to the outbound streams.
   */
  public function update() {
    $temporary = array();
    $all_exhausted = true;
    foreach ($this->inboundStreams as $inbound) {
      if (!$inbound->isExhausted()) {
        $all_exhausted = false;
      }
      
      if ($inbound->canRead()) {
        $type = $inbound->getSourceType();
        $objects = $inbound->read();
        
        if ($this->targetType !== null) {
          $converter = new TypeConverter();
          $type = $this->targetType;
          foreach ($objects as $key => $obj) {
            $objects[$key] = $converter->convert($obj, $type);
          }
        }
        
        $temporary[$type] = idx($temporary, $type, array());
        $temporary[$type] = array_merge($temporary[$type], $objects);
      }
    }
    
    if ($all_exhausted) {
      // No further update() calls will result in more activity.
      return false;
    }
    
    $writable_outbounds = array();
    foreach ($this->outboundStreams as $outbound) {
      if ($outbound->canWrite()) {
        $type = $outbound->getTargetType();
        $writable_outbounds[$type] = idx($writable_outbounds, $type, array());
        $writable_outbounds[$type][] = $outbound;
      }
    }
    
    switch ($this->distributionMethod) {
      case self::DIST_METHOD_SPLIT:
        foreach ($temporary as $type => $objects) {
          $writers = idx($writable_outbounds, $type);
          
          if ($writers === null || count($writers) === 0) {
            // We have nowhere to send these objects.
            throw new Exception(
              'Unable to pipe objects of type '.$type.' anywhere; there is '.
              'no outbound stream that will accept them.  Have you tried '.
              'casting them with `pipe -c '.$type.'`?');
          }
          
          $total_objects = count($objects);
          $total_writers = count($writers);
          $per_writer = (int)floor($total_objects / $total_writers);
          $remaining = $total_objects % $total_writers;
          $i = 0;
          foreach ($writers as $key => $outbound) {
            $x = 0;
            if ($key === last_key($writers)) {
              $x = $remaining;
            }
            
            $outbound->write(array_slice($objects, $i, $i + $per_writer + $x));
            $i += $per_writer;
          }
        }
        break;
    }
  }
  
}