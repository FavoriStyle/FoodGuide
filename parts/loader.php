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

(function(){
    $settings = [
		'user' => 'FavoriStyle',
		'repo' => 'FoodGuide'
    ];
    $parts = [
        //parts to be loaded
        //'reformat_css',
        'disable-emojis/disable-emojis',
        'user_debug',
        'final_buffer',
        'native_admin_addon',
        'easyadmin_addon',

    ];
    $debug_parts = [
        //parts to be loaded only with --debug or --devel key
        'api',
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