<?php

/*
Plugin Name: FGC OpenParts
Description: Adds avaiability to load external code from GitHub
Plugin URI: https://github.com/FavoriStyle/FoodGuide/
Version: 0.0.5-c
Author: KaMeHb-UA
Author URI: https://github.com/KaMeHb-UA
License: MIT
*/

require($_SERVER['DOCUMENT_ROOT'] . '/openparts_secrets.php');

define('_USER_DEBUG_MODE', (isset($_GET['--debug']) || isset($_GET['--devel'])));

if (isset($_GET['--remove-cache'])){
	if (isset($_GET['secret']) && $_GET['secret'] == Secrets::$delete_cache_secret){
		class _Fops{
			public static function deleteDir($dirPath){
				if (! is_dir($dirPath)){
					throw new InvalidArgumentException("$dirPath must be a directory");
				}
				if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
					$dirPath .= '/';
				}
				$files = glob($dirPath . '*', GLOB_MARK);
				foreach ($files as $file){
					if (is_dir($file)){
						self::deleteDir($file);
					} else {
						unlink($file);
					}
				}
				rmdir($dirPath);
			}		
		}
		_Fops::deleteDir('openparts/cache');
		die('Кэш очищен');
	}
	die('Кэш НЕ БЫЛ очищен');
}

(function(){
	function makeDir($path){
		return file_exists($path) || mkdir($path, 0755, true);
	}
	function do_cache($file_url, $unsafe){
		makeDir(WPMU_PLUGIN_DIR . '/openparts');
		makeDir(WPMU_PLUGIN_DIR . '/openparts/cache');
		$cache = false;
		if(_USER_DEBUG_MODE){
			file_put_contents(WPMU_PLUGIN_DIR . '/openparts/cache/unsafe_list.json', '', FILE_APPEND | LOCK_EX);
			$list = json_decode(file_get_contents(WPMU_PLUGIN_DIR . '/openparts/cache/unsafe_list.json'), true);
			if (!$list) $list = [];
			foreach ($list as $key => $value){
				if ($key == $file_url){
					$cache = WPMU_PLUGIN_DIR . '/openparts/cache/unsafe_' . $value . '.php';
				}
			}
			if (!$cache){
				$fname = md5(uniqid(rand(), true));
				$cache = WPMU_PLUGIN_DIR . '/openparts/cache/unsafe_' . $fname . '.php';
				file_put_contents($cache, file_get_contents($file_url), LOCK_EX);
				$list[$file_url] = $fname;
				file_put_contents(WPMU_PLUGIN_DIR . '/openparts/cache/unsafe_list.json', json_encode($list), LOCK_EX);
			} else file_put_contents($cache, file_get_contents($file_url), LOCK_EX);
			return $cache;
		} else {
			file_put_contents(WPMU_PLUGIN_DIR . '/openparts/cache/list.json', '', FILE_APPEND | LOCK_EX);
			$list = json_decode(file_get_contents(WPMU_PLUGIN_DIR . '/openparts/cache/list.json'), true);
			if (!$list) $list = [];
			foreach ($list as $key => $value){
				if ($key == $file_url){
					$cache = WPMU_PLUGIN_DIR . '/openparts/cache/' . $value . '.php';
				}
			}
			if (!$cache){
				$fname = md5(uniqid(rand(), true));
				$cache = WPMU_PLUGIN_DIR . '/openparts/cache/' . $fname . '.php';
				file_put_contents($cache, file_get_contents($file_url), LOCK_EX);
				$list[$file_url] = $fname;
				file_put_contents(WPMU_PLUGIN_DIR . '/openparts/cache/list.json', json_encode($list), LOCK_EX);
			}
			return $cache;
		}
	}
    function url_require($src){
        return require(do_cache($src, _USER_DEBUG_MODE));
	}
    function url_require_once($src){
        return require_once(do_cache($src, _USER_DEBUG_MODE));
	}
	url_require_once((function($settings){
		return "https://raw.githubusercontent.com/$settings[user]/$settings[repo]/master/$settings[file]";
	})([
		'user' => 'FavoriStyle',
		'repo' => 'FoodGuide',
		'file' => 'parts/loader.php',
	]));
})();

?>