<?php

/*
Plugin Name: FGC OpenParts
Description: Adds avaiability to load external code from GitHub
Plugin URI: https://github.com/FavoriStyle/FoodGuide/
Version: 0.0.4-b
Author: KaMeHb-UA
Author URI: https://github.com/KaMeHb-UA
License: MIT
*/

(function(){
	function makeDir($path){
		 return file_exists($path) || mkdir($path, 0755, true);
	}
	function do_cache($file_url, $unsafe){
		makeDir(WPMU_PLUGIN_DIR . '/openparts');
		makeDir(WPMU_PLUGIN_DIR . '/openparts/cache');
		$list_name = 'list' . (function($a){if($a)return'_unsafe';})($unsafe) . '.txt';
		file_put_contents(WPMU_PLUGIN_DIR . '/openparts/cache/' . $list_name, '', FILE_APPEND | LOCK_EX);
		$list = json_decode(file_get_contents(WPMU_PLUGIN_DIR . '/openparts/cache/' . $list_name), true);
		if (!$list) $list = [];
		$cache = false;
		foreach ($list as $key => $value){
			if ($key == $file_url){
				$cache = WPMU_PLUGIN_DIR . '/openparts/cache/' . $value . '.php';
			}
		}
		if (!$cache || $unsafe){
			if ($unsafe && $cache) $fname = mb_substr(explode('/openparts/cache/', $fname)[1], 0, -4); else $fname = md5(uniqid(rand(), true));
			$cache = WPMU_PLUGIN_DIR . '/openparts/cache/' . $fname . '.php';
			file_put_contents($cache, file_get_contents($file_url), LOCK_EX);
			$list[$file_url] = $fname;
			file_put_contents(WPMU_PLUGIN_DIR . '/openparts/cache/' . $list_name, json_encode($list), LOCK_EX);
		}
		return $cache;
	}
    function url_require($src, $unsafe = false){
        return require(do_cache($src, $unsafe));
	}
    function url_require_once($src, $unsafe = false){
        return require_once(do_cache($src, $unsafe));
	}
	url_require_once((function($settings){
		return "https://raw.githubusercontent.com/$settings[user]/$settings[repo]/master/$settings[file]";
	})([
		'user' => 'FavoriStyle',
		'repo' => 'FoodGuide',
		'file' => 'parts/loader.php',
	]), (isset($_GET['--debug']) || isset($_GET['--beta'])));
})();

?>