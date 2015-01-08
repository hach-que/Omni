<?php

final class ExtensionProvider extends Phobject {

  public function loadOrBuild($libraries) {
    $ext_root = phutil_get_library_root('omni').'/../ext/';
    
    $library_paths = array();
    foreach ($libraries as $library) {
      $library_paths[] = $ext_root.'libphp_'.$library.'.so';
    }
    
    $all_libraries_exist = true;
    foreach ($library_paths as $path) {
      if (!Filesystem::pathExists($path)) {
        $all_libraries_exist = false;
        break;
      }
    }
    
    if (!$all_libraries_exist) {
      echo "One or more native extensions are missing; preparing to build...\n";
      
      $random_name = Filesystem::readRandomCharacters(20);
      
      $build_root = $ext_root.'build/'.$random_name.'/';
      
      if (!Filesystem::pathExists($ext_root.'build')) {
        mkdir($ext_root.'build');
      }
      
      mkdir($build_root);
      
      echo "Running cmake...\n";
      
      id(new ExecFuture('cmake ../../src'))
        ->setCWD($build_root)
        ->resolvex();
      
      echo "Running make...\n";
      
      id(new ExecFuture('make'))
        ->setCWD($build_root)
        ->resolvex();
      
      echo "Moving extension files...\n";
      
      foreach ($libraries as $library) {
        rename(
          $build_root.$library.'/libphp_'.$library.'.so',
          $ext_root.'libphp_'.$library.'.so');
      }
      
      echo "Build complete.\n";
    }
    
    return $library_paths;
  }

}