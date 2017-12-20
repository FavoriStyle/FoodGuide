<?php

/*
Plugin Name: FGC OpenParts parts loader
Description: Adds avaiability to load external code from GitHub
Plugin URI: https://github.com/FavoriStyle/FoodGuide/
Version: 0.0.5-b
Author: KaMeHb-UA
Author URI: https://github.com/KaMeHb-UA
License: MIT
*/

define('JS_LOADER_CHANNEL', 'stable'); // beta or stable
define('CSS_LOADER_CHANNEL', 'stable'); // beta or stable

(function(){
    $settings = [
		'user' => 'FavoriStyle',
		'repo' => 'FoodGuide'
    ];
    $parts = [
        //parts to be loaded
        'cacher',
        'static_globals',
        'api',
        'disable-emojis/disable-emojis',
        'user_debug',
        'final_buffer',
        'native_admin_addon',
        'easyadmin_addon',
        'static_loader',
    ];
    $debug_parts = [
        //parts to be loaded only with --debug or --devel key
    ];

    if(_USER_DEBUG_MODE){
        foreach ($debug_parts as $part){
            $parts[] = $part;
        }
    }
    foreach ($parts as $part){
        url_require("https://raw.githubusercontent.com/$settings[user]/$settings[repo]/master/parts/$part.php");
    }
})();

?>