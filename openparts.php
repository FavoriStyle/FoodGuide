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

function dump($var){
	ob_start();
	var_dump($var);
	return substr(ob_get_clean(), 0, -1);
}

function mysql_result($sql, $debugConsole = false){
	$dbConfig = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/dbconfig.json'));
	if (!$debugConsole) $debugConsole = new class{
		function log(){}
		function warn(){}
		function error(){}
	};
	$sql = preg_replace_callback('/(FROM|JOIN|INTO|UPDATE)\s+`(.+?)`/ms', function($matches) use ($debugConsole, $dbConfig){
		if(!(mb_strpos($matches[2], $dbConfig -> table_prefix) === 0)) $matches[2] = $dbConfig -> table_prefix . $matches[2];
		return $matches[1] . ' `' . $matches[2] . '`';
	}, $sql);
	$debugConsole -> log($sql);
	$mysqli = new mysqli($dbConfig -> host, $dbConfig -> user, $dbConfig -> pass, $dbConfig -> db);
	if (!$mysqli -> connect_errno){
		$res = [];
		$result = $mysqli -> query($sql);
		if($result){
			if($result === true) return $result;
			while($res[] = $result -> fetch_assoc()){/*like a null loop*/}
			array_pop($res);
			$debugConsole -> log($res);
			return $res;
		}
	} else $debugConsole -> error('DB Connection error ' . $mysqli -> connect_errno . ' ($mysqli -> error: ' . dump($mysqli -> error) . ')');
	return false;
}

function get_request($url, $headers){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);	
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

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
		try{ _Fops::deleteDir('openparts/cache'); } catch(Exception $e){}
		$commit_sha = json_decode(get_request('https://api.github.com/repos/FavoriStyle/FoodGuide/commits/master', array(
			'Accept: application/vnd.github.v3+json',
			'User-Agent: Mozilla/5.0 (compatible; Foodguide/0.9; +https://foodguide.in.ua)'
		))) -> sha;
		mysql_result("UPDATE `openparts_cache` SET id='$commit_sha'");
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