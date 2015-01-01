#!/bin/omni

$array = @(
  abc,
  def,
  ghi,
)

foreach $array as $key => $value {
  echo $key
  echo $value
}

