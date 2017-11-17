<?php

/*
Plugin Name: FGC OpenParts cacher
Description: Adds avaiability to cache static entities (any JSON-compatible entity)
Plugin URI: https://github.com/FavoriStyle/FoodGuide/
Version: 0.0.1-a+pre
Author: KaMeHb-UA
Author URI: https://github.com/KaMeHb-UA
License: Ms-PL
*/

class OpenpartsCache{
    private static $cache_file = WPMU_PLUGIN_DIR . '/openparts/cache/static.db';
    private static $list = [];
    private static $inited = false;
    public static function init(){
        if(!self::$inited){
            $tmp = file_get_contents(self::$cache_file);
            if($tmp){
                $tmp = json_decode($tmp, true);
                if ($tmp) self::$list = $tmp;
            }
            self::$inited = true;
        }
    }
    public static function cache(){
        $count = func_num_args();
        $args = func_get_args();
        if ($count && $count == 1){
            return self::$list[$args[0]];
        } elseif($count){
            self::$list[$args[0]] = $args[1];
            file_put_contents(self::$cache_file, self::$list, LOCK_EX);
        }
    }
}

OpenpartsCache::init();

?>