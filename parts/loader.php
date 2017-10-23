<?php

/*
Plugin Name: FGC OpenParts parts loader
Description: Adds avaiability to load external code from GitHub
Plugin URI: https://github.com/FavoriStyle/FoodGuide/
Version: 0.0.4-a
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
        'reformat_css',
        'disable-emojis/disable-emojis',
        'user_debug',

    ];
    $debug_parts = [
        //parts to be loaded only with --debug or --beta key
        //'delete_css_js',
    ];
    $enable_debug = (isset($_GET['--debug']) || isset($_GET['--beta']));

    if($enable_debug){
        foreach ($debug_parts as $part) {
            $parts[] = $part;
        }
    }
    foreach ($parts as $part){
        url_require("https://raw.githubusercontent.com/$settings[user]/$settings[repo]/master/parts/$part.php", $enable_debug);
    }
})();

?>