<?php

    $dashicons = new class{
        private $stack = [];
        public function __get($name){
            if(!in_array($name, $this -> stack)) $this -> stack[] = $name;
            return "dashicons-fa-u-$name";
        }
        public function __construct(){
            add_action('admin_enqueue_scripts', function(){
                $css = '';
                foreach($this -> stack as $symbol){
                    $css .= ",.dashicons-fa-u-$symbol:before";
                }
                $css = mb_substr($css, 1) . '{font-family:FontAwesome !important}';
                foreach($this -> stack as $symbol){
                    $css .= ".dashicons-fa-u-$symbol:before{content:\"\\$symbol\"}";
                }
                echo "<style>$css</style>";
            });
        }
    };

    $templates = new class{
        private $cache = [];
        public function __get($name){
            if(!isset($this -> cache[$name])) $this -> cache[$name] = new class($name){
                private $str = '';
                public function __construct($name){
                    $this -> str = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/FGC/templates/' . $name . '.tpl');
                    $this -> str = ($this -> str ? $this -> str : '');
                }
                public function __toString(){
                    return $this -> str;
                }
                public function set($what, $val){
                    $this -> str = implode($val, explode('{' . $what . '}', $this -> str));
                }
            };
            return $this -> cache[$name];
        }
    };

    add_action('admin_menu', function() use (&$dashicons, &$templates){
        add_menu_page('Multiple -> Single title', 'Multiple -> Single', 'loco_admin', 'multiple-single-custom-matcher', function() use (&$templates){
            echo $templates -> ea_item_add;
        }, $dashicons -> f145);
    });
   
?>