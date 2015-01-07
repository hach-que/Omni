<?php

final class ChangeDirectoryBuiltin extends Builtin {

  public function getName() {
    return 'cd';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    PipeInterface $stdin,
    PipeInterface $stdout,
    PipeInterface $stderr) {
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(null, "cd stdout"),
      'output_new_dir' => !$stdout->isConnectedToTerminal(),
    );
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    return array(
      array(
        'name' => 'dot-dot-logical',
        'short' => 'L',
        'help' => 
          'Handle the operand dot-dot logically; symbolic link components '.
          'shall not be resolved before dot-dot components are processed.'
      ),
      array(
        'name' => 'dot-dot-physical',
        'short' => 'P',
        'help' => 
          'Handle the operand dot-dot physically; symbolic link components '.
          'shall be resolved before dot-dot components are processed.'
      ),
      array(
        'name' => 'directory',
        'wildcard' => true,
        'help' => 
          'The directory to change to, or - (hyphen) to change to the '.
          'previous directory.'
      ),
    );
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    
    // Implemented according to the POSIX manual for the "cd" command.
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull($this->getArguments($shell, $job, $prepare_data));
    
    $mode = 'logical';
    if ($parser->getArg('dot-dot-physical')) {
      $mode = 'physical';
    }
    
    $directory = null;
    if (count($parser->getArg('directory')) > 0) {
      $directory = head($parser->getArg('directory'));
    }
    
    $home_env = getenv('HOME');
    
    if ($directory === null) {
      if ($home_env === null) {
        // TODO We should probably look up the home directory
        // via /etc/passwd if HOME isn't set.
        throw new Exception('No directory set and HOME is not present');
      }
      
      $directory = $home_env;
    }
    
    $current_path = null;
    $is_absolute = false;
    if (strlen($directory) >= 1 && $directory[0] === '/') {
      $current_path = $directory;
      $is_absolute = true;
    }
    
    // Not defined in the POSIX specification, but behaviour that is
    // observed in Bash; paths who have the first component of ~ have
    // this translated into the user's home directory.
    if ((strlen($directory) === 1 && $directory[0] === '~') ||
        (strlen($directory) >= 2 && $directory[0] === '~' && $directory[1] === '/')) {
      if ($home_env === null) {
        // TODO We should probably look up the home directory
        // via /etc/passwd if HOME isn't set.
        throw new Exception('~ specified in path and HOME is not present');
      }
      
      $directory = $home_env.substr($directory, 1);
    } 
    
    if (!$is_absolute) {
      $components = explode('/', $directory);
      
      if (head($components) === '.' || head($components) === '..') {
        $current_path = $directory;
      } else {
        $cd_paths = explode(':', getenv('CDPATH'));
        foreach ($cd_paths as $cd_path) {
          if ($cd_path !== '') {
            $temp_cd_path = $cd_path;
            if ($cd_path[strlen($cd_path) - 1] !== '/') {
              $temp_cd_path .= '/';
            }
            
            if (is_dir($temp_cd_path.$directory)) {
              $current_path = $temp_cd_path.$directory;
              break;
            }
          } else {
            if (is_dir('./'.$directory)) {
              $current_path = './'.$directory;
              break;
            }
          }
        }
      }
      
      if ($current_path === null) {
        $current_path = $directory;
      }
    }
    
    if ($mode === 'logical') {
      if (strlen($current_path) > 0 && $current_path[0] !== '/') {
        $pwd = getcwd();
        if (strlen($pwd) > 0 && $pwd[strlen($pwd) - 1] !== '/') {
          $pwd .= '/';
        }
        
        $current_path = $pwd.$current_path;
      }
      
      $components = explode('/', $current_path);
      foreach ($components as $idx => $component) {
        if ($component === '') {
          unset($components[$idx]);
          continue;
        }
        
        if ($component === '.') {
          unset($components[$idx]);
          continue;
        }
        
        if ($components === '..') {
          if ($idx > 0 && $components[$idx - 1] !== '..') {
            $temp_path = implode('/', array_slice($components, 0, $idx - 1));
            if (is_dir($temp_path)) {
              throw new Exception("$temp_path does not refer to a directory");
            }
            
            unset($components[$idx]);
            unset($components[$idx - 1]);
          }
        }
      }
      
      $current_path = '/'.implode('/', $components);
    }
    
    // We don't need to change $current_path to PATH_MAX, because PHP handles
    // this for us.  So just jump straight to step 10 in the manual and attempt
    // to perform chdir.
    
    if (@chdir($current_path) === false) {
      throw new Exception("Unable to change directory to $current_path");
    }
    
    if ($prepare_data['output_new_dir']) {
      $stdout->write(new StructuredFile(getcwd()));
    }
    
    $stdout->closeWrite();
  }

}