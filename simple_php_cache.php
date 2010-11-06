<?php

class simple_php_cache {

    protected $_options = array(
        'num_directory_levels' => 1,    // Number of sub directories to use for storing cached files
        'filename_prefix' => '',         // Prefix to add to all stored files
        'umask_dir' => '0700',               // Umask used for permissions on sub-directories
        'umask_file' => '0666',              // Umask used for permissions on cache files
        'gzip_level' => '1'                 // Level of gzip compression to use, 0 for no gzip
    );


    public function __construct($cache_dir) {
        $this->_options['cache_dir'] = $cache_dir;
    }


    public function save($data, $filename) {
        clearstatcache();
        $path = $this->_path($filename);
        $file = $this->_full_file_path($filename);
        if ($this->_options['num_directory_levels'] > 0) {
            if (!is_writable($path)) {
                // Cache directory structure may need to be created
                $this->_create_sub_dir_structure($filename);
            }
            if (!is_writable($path)) {
                // A second check to make sure dir was created successfully
                return false;
            }
        }

        if ($this->_options['gzip_level'] > 0) $res = @file_put_contents($file, gzdeflate(json_encode($data), $this->_options['gzip_level']));   // JSON & gzip
        else $res = @file_put_contents($file, json_encode($data));  // JSON Only
        if ($res) @chmod($file, $this->_options['umask_file']);
        return $res;
    }


    public function load($filename) {
        $file = $this->_full_file_path($filename);
        //$data = $this->_fileGetContents($file);
        $data = file_get_contents($file);
        if ($this->_options['gzip_level'] > 0) $data = gzinflate($data);
        return json_decode($data, true);
    }


    // Remove a cache file
    public function remove($filename) {
        $result = true;
        $file = $this->_full_file_path($filename);
        if ($file) {
            $result = $result && @unlink($file);
        }
        return ($result);
    }


    public function clear($dir = null) {
        if (!$dir) $dir = $this->_options['cache_dir'];
        if ($dh = opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                if ($file != "." && $file != "..") {
        			if(is_dir($dir.$file)) {
        				if(!@rmdir($dir.$file)) { // If remove dir fails then it's not empty so we go deeper
                            $this->clear($dir.$file.'/');
        				}
        			}
        			else {
                       @unlink($dir.$file);
        			}
                }
            }
            closedir($dh);
        	if ($dir != $this->_options['cache_dir']) @rmdir($dir);
        }
    }


    /****************************************************
    ***********    SUPPORT FUNCTIONS *******************/

    // Make the directory strucuture for the given filename
    private function _create_sub_dir_structure($filename) {
        if ($this->_options['num_directory_levels'] <=0) {
            return true;
        }
        $pathbits = $this->_path($filename, true);
        foreach ($pathbits as $part) {
        	if (!is_dir($part)) {
            	@mkdir($part, $this->_options['umask_dir']);
        	    @chmod($part, $this->_options['umask_dir']);
        	}
        }
        return true;
    }


    // Make and return a file name (with path)
    private function _full_file_path($filename) {
        $path = $this->_path($filename);
        return $path . $this->_options['filename_prefix'] . $filename;
    }


    // Return the complete directory path of a filename
    private function _path($filename, $rtn_parts = false) {
        $pathbits = array();
        $cache_dir = $this->_options['cache_dir'];
        if ($this->_options['num_directory_levels']>0) {
            $hash = hash('adler32', $filename);
            //$hash = hash('md4', $filename);
            for ($i=0; $i < $this->_options['num_directory_levels']; $i++) {
                $cache_dir = $cache_dir . $this->_options['filename_prefix'] . substr($hash, 0, $i + 1) . DIRECTORY_SEPARATOR;
                $pathbits[] = $cache_dir;
            }
        }
        if ($rtn_parts) {
            return $pathbits;
        } else {
            return $cache_dir;
        }
    }

}