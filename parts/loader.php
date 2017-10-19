<?php

/*
Plugin Name: FGC OpenParts parts loader
Description: Adds avaiability to load external code from GitHub
Plugin URI: https://github.com/FavoriStyle/FoodGuide/
Version: 0.0.1
Author: KaMeHb-UA
Author URI: https://github.com/KaMeHb-UA
License: MIT
*/

(function(){
    $settings = [
		'user' => 'FavoriStyle',
		'repo' => 'FoodGuide'
	];
    foreach ([
        //parts to be loaded
        'reformat_css',
    ] as $part){
        url_require("https://raw.githubusercontent.com/$settings[user]/$settings[repo]/master/parts/$part.php");
    }
})();

?>