#!/bin/omni

: $file = $(new -t File test_file)

if ($file->exists) {
  : $file->delete();
}

echo ($file->fileName);
echo ($file->exists);

: $file->setContent("hello");

echo ($file->exists);
echo ($file->loadContent());

: $file->delete();

