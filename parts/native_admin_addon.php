<?php

    $dashicons = new class{
        private $stack = [];
        public function __get($name){
            if(!in_array($name, $this -> stack)) $this -> stack[] = $name;
            return "dashicons-fa-u-$name";
        }
        public function generateCSS(){
            $css = '';
            foreach($this -> stack as $symbol){
                $css .= ",.dashicons-fa-u-$symbol:before";
            }
            $css = mb_substr($css, 1) . '{font-family:FontAwesome;}';
            foreach($this -> stack as $symbol){
                $css .= ".dashicons-fa-u-$symbol:before{content:\"\\$symbol\";}";
            }
            return $css;
        }
    };

    add_action('admin_menu', function() use (&$dashicons){
        add_menu_page('Multiple -> Single title', 'Multiple -> Single', 'loco_admin', 'multiple-single-custom-matcher', function(){
            echo 'There will be a page';
        }, $dashicons -> f145);
    });

    add_action('admin_enqueue_scripts', function() use (&$dashicons){
        echo $dashicons -> generateCSS();
    });
   
?>