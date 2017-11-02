<?php

    $mysql_result = (function($func){
        return function($sql, $debugConsole = false) use ($func){
            if (!$debugConsole){
                $debugConsole = new class {
                    public function log($a){}
                    public function warn($a){}
                    public function error($a){}
                };
            }
            return $func($sql, $debugConsole);
        };
    })(function($sql, $debugConsole){
        $sql = preg_replace_callback('/FROM\s+`(.+?)`/ms', function($matches){
            global $wpdb;
            if(!(mb_strpos($matches[1], $wpdb -> prefix) === 0)) $matches[1] = $wpdb -> prefix . $matches[1];
            return 'FROM `' . $matches[1] . '`';
        }, $sql);
        $debugConsole -> log($sql);
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if (!$mysqli -> connect_errno){
            $res = [];
            $result = $mysqli -> query($sql);
            if($result){
                while($res[] = $result -> fetch_assoc()){/*like a null loop*/}
                array_pop($res);
                return $res;
            }
        }
        return false;
    });

    $FontAwesome = new class{
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

    add_action('admin_menu', function() use (&$FontAwesome, &$templates, $mysql_result){
        add_menu_page('Multiple -> Single title', 'Multiple -> Single', 'loco_admin', 'multiple-single-custom-matcher', function() use (&$templates, $mysql_result){
            $templates -> multiple_to_single_matching -> set('heading', __('Multiple and single categories names matching', 'ait-admin'));
            $templates -> multiple_to_single_matching -> set('mtsm_tip', 'Tip will be here');
            $templates -> multiple_to_single_matching -> set('main_matching', json_encode($mysql_result('SELECT * FROM `categories_singles`')));
            echo $templates -> multiple_to_single_matching;
        }, $FontAwesome -> f145);
    });
   
?>