<?php

require_once 'simple_php_cache.php';

$cache = new simple_php_cache('cache/' );

$data = $cache->load('save_filename5');

var_dump($data);