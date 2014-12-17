<?php

final class ListDirectoryBuiltin extends Builtin {

  public function getName() {
    return 'ls';
  }
  
  public function prepare(
    Shell $shell,
    Job $job,
    array $arguments,
    Pipe $stdin,
    Pipe $stdout,
    Pipe $stderr) {
    
    return array(
      'stdout' => $stdout->createInboundEndpoint(),
    );
  }
  
  public function getArguments(
    Shell $shell,
    Job $job,
    array $prepare_data) {
    
    $all_arguments = array(
      array(
        'name' => 'all',
        'short' => 'a',
        'help' => 
          'Do not ignore entries starting with \'.\'.'
      ),
      array(
        'name' => 'almost-all',
        'short' => 'A',
        'help' => 
          'Do not list implied \'.\' and \'..\'.'
      ),
      array(
        'name' => 'ignore-backups',
        'short' => 'B',
        'help' => 
          'Do not list implied entries ending with \'~\'.'
      ),
      array(
        'name' => 'ctime',
        'short' => 'c',
        'help' => 
          'With -lt: sort by and show ctime (time of last modification of file status '.
          'information); with -l: show ctime and sort by name; otherwise sort by ctime, '.
          'newest first.  '.
          'The printing component of this option only has an effect if the '.
          'standard output of this command is encoded as a byte stream.  The '.
          'sort effect applies regardless of standard output encoding.'
      ),
      array(
        'name' => 'directory',
        'short' => 'd',
        'help' => 
          'List directories themselves, not their contents.'
      ),
      array(
        'name' => 'dereference-command-line',
        'short' => 'H',
        'help' => 
          'Follow symbolic links listed on the command line.'
      ),
      array(
        'name' => 'dereference-command-line-symlink-to-dir',
        'help' => 
          'Follow each command line symbolic link that points to a directory.'
      ),
      array(
        'name' => 'hide',
        'param' => 'PATTERN',
        'help' => 
          'Do not list implied entries match shell PATTERN (overridden by -a or -A).'
      ),
      array(
        'name' => 'ignore',
        'short' => 'I',
        'param' => 'PATTERN',
        'help' => 
          'Do not list implied entries matching shell PATTERN.'
      ),
      array(
        'name' => 'dereference',
        'short' => 'L',
        'help' => 
          'When showing file information for a symbolic link, show '.
          'information for the file the link references rather than '.
          'for the link itself.'
      ),
      array(
        'name' => 'reverse',
        'short' => 'r',
        'help' => 
          'Reverse order while sorting.'
      ),
      array(
        'name' => 'recursive',
        'short' => 'R',
        'help' => 
          'List subdirectories recursively.'
      ),
      array(
        'name' => 'sort-by-size',
        'short' => 'S',
        'help' => 
          'Sort by file size.'
      ),
      array(
        'name' => 'sort',
        'param' => 'WORD',
        'help' => 
          'Sort by WORD instead of name: none (-U), size (-S), time (-t), '.
          'version (-v), extension (-X).'
      ),
      array(
        'name' => 'time',
        'param' => 'WORD',
        'help' => 
          'With -l, show time as WORD instead of default modification time: '.
          '\'atime\', \'access\' or \'use\' (-u); \'ctime\' or \'status\' (-c).  '.
          'Also use specified time as sort key if --sort=time is set.'.
          'The printing component of this option only has an effect if the '.
          'standard output of this command is encoded as a byte stream.  The '.
          'sort effect applies regardless of standard output encoding.'
      ),
      array(
        'name' => 'modification-time',
        'short' => 't',
        'help' => 
          'Sort by modification time, newest first.'
      ),
      array(
        'name' => 'access-time',
        'short' => 'u',
        'help' => 
          'With -lt: sort by and show access time; with l: show access time '.
          'and sort by name; otherwise: sort by access time.  '.
          'The printing component of this option only has an effect if the '.
          'standard output of this command is encoded as a byte stream.  The '.
          'sort effect applies regardless of standard output encoding.'
      ),
      array(
        'name' => 'no-sort',
        'short' => 'U',
        'help' => 
          'Do not sort entries.'
      ),
      array(
        'name' => 'natural-sort-version',
        'short' => 'v',
        'help' => 
          'Natural sort of (version) numbers within text.'
      ),
      array(
        'name' => 'sort-extension',
        'short' => 'X',
        'help' => 
          'Sort alphabetically by entry extension.'
      ),
      array(
        'name' => 'path',
        'wildcard' => true,
        'help' => 'The paths to list.',
      )
    );
    
    $is_byte_stream_stdout = false;
    $stdout_or_null = idx($prepare_data, 'stdout');
    if ($stdout_or_null !== null && $stdout_or_null === Endpoint::FORMAT_BYTE_STREAM) {
      $is_byte_stream_stdout = true;
    }
    
    // We show different options if the command is outputting to a byte stream.  The 
    // reason for this is that conventionally there's a bunch of options for Bash's
    // version of ls relating to output of data, but when we're outputting structured
    // information in Omni, presentation isn't important (because we print the structured
    // data).  So when we're outputting to a byte stream, we assume that the command
    // might be outputting data to a native executable (where presentation is important),
    // but otherwise we only show a subset of arguments that actually affect
    // functionality.
    if ($is_byte_stream_stdout) {
      $all_arguments += array(
        array(
          'name' => 'author',
          'help' => 
            'With -l, print the author of each file.'
        ),
        array(
          'name' => 'escape',
          'short' => 'b',
          'help' => 
            'Print C-style escapes for nongraphic characters.'
        ),
        array(
          'name' => 'block-size',
          'param' => 'SIZE',
          'help' => 
            'Scale sizes by SIZE before printing them; e.g. \'--block-size=M\' '.
            'prints sizes in units of 1,048,576 bytes; see SIZE format below.'
        ),
        array(
          'name' => 'columns',
          'short' => 'C',
          'help' => 
            'List entries by columns.'
        ),
        array(
          'name' => 'color',
          'param' => 'WHEN',
          'help' => 
            'Colorize the output; WHEN can be \'never\', \'auto\', or '.
            '\'always\' (the default).'
        ),
        array(
          'name' => 'dired',
          'short' => 'D',
          'help' => 
            'Not applicable under Omni.'
        ),
        array(
          'name' => 'f',
          'short' => 'f',
          'help' => 
            'Do not sort, enable -aU, disable -ls --color'
        ),
        array(
          'name' => 'classify',
          'short' => 'F',
          'help' => 
            'Append indicator (one of */=>@|) to entries.'
        ),
        array(
          'name' => 'file-type',
          'help' => 
            'Like --classify, except do not append \'*\'.'
        ),
        array(
          'name' => 'format',
          'param' => 'WORD',
          'help' => 
            'WORD can be one of \'across\' (equivalent of -x), '.
            '\'commas\' (equivalent of -m), \'horizontal\' (equivalent of -x), '.
            '\'long\' (equivalent of -l), \'single-column\' (equivalent of -1), '.
            '\'verbose\' (equivalent of -l), \'vertical\' (equivalent of -c).'
        ),
        array(
          'name' => 'full-time',
          'help' => 
            'Like -l --time-style=full-iso.'
        ),
        array(
          'name' => 'no-owner',
          'short' => 'g',
          'help' => 
            'Like -l, but do not list owner.'
        ),
        array(
          'name' => 'group-directories-first',
          'help' => 
            'Group directories before files.  Can be augmented with a --sort  '.
            'option, but any use of --sort=none (-U) disables grouping.'
        ),
        array(
          'name' => 'no-group',
          'short' => 'G',
          'help' => 
            'In a long listing, don\'t print group names.'
        ),
        array(
          'name' => 'human-readable',
          'short' => 'h',
          'help' => 
            'With -l and / or -s, print human readable sizes (e.g. 1K 234M 2G).'
        ),
        array(
          'name' => 'si',
          'help' => 
            'Like -h, but use powers of 1000 not 1024.'
        ),
        array(
          'name' => 'indicator-style',
          'param' => 'WORD',
          'help' => 
            'Append indicator with style WORD to entry names: none (default), '.
            'slash (-p), file-type (--file-type), classify (-F).'
        ),
        array(
          'name' => 'inode',
          'short' => 'i',
          'help' => 
            'Print the index number of each file.'
        ),
        array(
          'name' => 'kibibytes',
          'short' => 'k',
          'help' => 
            'Default to 1024-byte blocks for disk usage.'
        ),
        array(
          'name' => 'long-listing',
          'short' => 'l',
          'help' => 
            'Use a long listing format.'
        ),
        array(
          'name' => 'fill-width-comma',
          'short' => 'm',
          'help' => 
            'Fill width with a comma seperated list of entries.'
        ),
        array(
          'name' => 'numeric-uid-gid',
          'short' => 'n',
          'help' => 
            'Like -l, but list numeric user and group IDs.'
        ),
        array(
          'name' => 'literal',
          'short' => 'N',
          'help' => 
            'Print raw entry names (don\'t treat e.g. control characters specically).'
        ),
        array(
          'name' => 'o-no-group',
          'short' => 'o',
          'help' => 
            'Like -l, but do not list group information.'
        ),
        array(
          'name' => 'slash-indicator',
          'short' => 'p',
          'help' => 
            'Equivalent to \'--indicator-style=slash\'.'
        ),
        array(
          'name' => 'hide-control-chars',
          'short' => 'q',
          'help' => 
            'Print ? instead of nongraphic characters.'
        ),
        array(
          'name' => 'show-control-chars',
          'help' => 
            'Show nongraphic characters as-is (the default, unless program is \'ls\' '.
            'and output is a terminal).'
        ),
        array(
          'name' => 'quote-name',
          'short' => 'Q',
          'help' => 
            'Enclose entry names in double quotes.'
        ),
        array(
          'name' => 'quoting-style',
          'param' => 'WORD',
          'help' => 
            'Use quoting style WORD for entry names: literal, locale, shell, '.
            'shell-always, c, escape.'
        ),
        array(
          'name' => 'size',
          'short' => 's',
          'help' => 
            'Print the allocated size of each file, in blocks.'
        ),
        array(
          'name' => 'time-style',
          'param' => 'WORD',
          'help' => 
            'With -l, show time using style STYLE: full-iso, long-iso, iso, '.
            'locale, or +FORMAT; FORATM is interpreted like in \'date\'; if '.
            'FORMAT is FORMAT1<newline>FORMAT2, then FORMAT1 applies to '.
            'non-recent files and FORMAT2 to recent files; if STYLE is '.
            '\'posix-\', STYLE takes effect only outside the POSIX locale.'
        ),
        array(
          'name' => 'tabsize',
          'short' => 'T',
          'param' => 'COLS',
          'help' => 
            'Assume tab stops at each COLS instead of 8.'
        ),
        array(
          'name' => 'width',
          'short' => 'w',
          'param' => 'COLS',
          'help' => 
            'Assume screen width instead of current value.'
        ),
        array(
          'name' => 'lines',
          'short' => 'x',
          'help' => 
            'List entries by lines instead of columns.'
        ),
        array(
          'name' => 'context',
          'short' => 'Z',
          'help' => 
            'Print any security context of each file.'
        ),
        array(
          'name' => 'one-file-per-line',
          'short' => '1',
          'help' => 
            'List one file per line.'
        ),
      );
    }
    
    return $all_arguments;
  }
  
  public function run(
    Shell $shell,
    Job $job, 
    array $arguments, 
    array $prepare_data) {
    
    $stdout = idx($prepare_data, 'stdout');
    
    $parser = new PhutilArgumentParser($arguments);
    $parser->parseFull($this->getArguments($shell, $job, $prepare_data));
    
    $paths = $parser->getArg('path');
    if (count($paths) === 0) {
      $paths[] = '.';
    }
    
    foreach ($paths as $path) {
      if (Filesystem::pathExists($path)) {
        $entries = Filesystem::listDirectory($path);
        foreach ($entries as $entry) {
          $stdout->write(new StructuredFile(rtrim($path, '/').'/'.$entry));
        }
      }
    }
    
    $stdout->closeWrite();
  }

}