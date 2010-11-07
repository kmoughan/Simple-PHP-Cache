<?php

class simple_php_cache {

    protected $_options = array(
        'num_directory_levels' => 1,        // Number of sub directories to use for storing cached files
        'filename_prefix' => '',            // Prefix to add to all stored files
        'umask_dir' => '0700',              // Umask used for permissions on sub-directories
        'umask_file' => '0666',             // Umask used for permissions on cache files
        'gzip_level' => '0',                // Level of gzip compression to use, 0 for no gzip
        'serialize_method' => 'json'        // Either json or serialize, json is faster but more limited, see notes
    );


    /**
     * Initialise the class
     *
     * @param  string  $cache_dir  Root path to the cache directory
     * @return void
     */
    public function __construct($cache_dir) {
        $this->_options['cache_dir'] = $cache_dir;
    }


    /**
     * Serialize and add data to cache
     *
     * @param  array   $data  Data to be cached
     * @param  string  $filename  Name of cache file
     * @return bool    Status
     */
    public function save($data, $filename) {
        clearstatcache();
        $path = $this->_gen_path($filename);
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

        if ($this->_options['gzip_level'] > 0) $res = @file_put_contents($file, gzdeflate($this->_serialize($data), $this->_options['gzip_level']));   // Serialize & gzip
        else $res = @file_put_contents($file, $this->_serialize($data));  // Serialize Only
        if ($res) @chmod($file, $this->_options['umask_file']);
        return $res;
    }


    /**
     * Retrieve data from the cache
     *
     * @param  string   $filename   The filename to retrieve
     * @return array
     */
    public function load($filename) {
        $file = $this->_full_file_path($filename);
        //$data = $this->_fileGetContents($file);
        $data = file_get_contents($file);
        if ($this->_options['gzip_level'] > 0) $data = gzinflate($data);
        return $this->_unserialize($data);
    }



    /**
     * Remove a cache file
     *
     * @param  string   $filename   Cache filename to remove
     * @return bool     Status
     */
    public function remove($filename) {
        $result = true;
        $file = $this->_full_file_path($filename);
        if ($file) {
            $result = $result && @unlink($file);
        }
        return ($result);
    }


    /**
     * Empty the entire cache directory
     *
     * @param  string   $dir    Directory path to empty, defaults to cache_dir on first iteration
     * @return void
     */
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





    /********************    SUPPORT FUNCTIONS *******************/





    /**
     * Create the directory strucuture for the given filename
     *
     * @param  string   $filename   Filename
     * @return bool     Status
     */
    private function _create_sub_dir_structure($filename) {
        if ($this->_options['num_directory_levels'] <=0) {
            return true;
        }
        $pathbits = $this->_gen_path($filename, true);
        foreach ($pathbits as $part) {
        	if (!is_dir($part)) {
            	@mkdir($part, $this->_options['umask_dir']);
        	    @chmod($part, $this->_options['umask_dir']);
        	}
        }
        return true;
    }


    /**
     * Compute the full hashed file path for $filename
     *
     * @param  string   $filename   Filename
     * @return string   Computed filepath including the original filename
     */
    private function _full_file_path($filename) {
        return $this->_gen_path($filename) . $this->_options['filename_prefix'] . $filename;
    }


    /**
     * Generate/Compute the cache file path for the specified filename
     *
     * @param  string   $filename   Filename
     * @param  bool     $rtn_parts  Whether to return path string or an array or path parts
     * @return mixed
     */
    private function _gen_path($filename, $rtn_parts = false) {
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


    /**
     * Serialize the data according to defined options
     *
     * @param  array    $data   Data to serialize
     * @return string   Serialized data string
     */
    private function _serialize($data) {
        if ($this->_options['serialize_method'] == 'json') {
            return json_encode($data);
        }
        else {
            return serialize($data);
        }
    }


    /**
     * Unserialize the data according to defined options
     *
     * @param  string   $data   String to be unserialized
     * @return array    Unserialized data
     */
    private function _unserialize($data) {
        if ($this->_options['serialize_method'] == 'json') {
            return json_decode($data, true);
        }
        else {
            return unserialize($data);
        }
    }
}