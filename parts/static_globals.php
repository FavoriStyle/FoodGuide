<?php

    class staticGlobals{
        private static $debugConsole = null;
        public static function init(){
            self::$debugConsole = new class{
                public function log($a){}
                public function warn($a){}
                public function error($a){}
            };
        }
        public static function mysql_result($sql, $debugConsole = false){
            if (!$debugConsole) $debugConsole = self::$debugConsole;
            $sql = preg_replace_callback('/FROM\s+`(.+?)`/ms', function($matches){
                global $wpdb;
                if(!(mb_strpos($matches[1], $wpdb -> prefix) === 0)) $matches[1] = $wpdb -> prefix . $matches[1];
                return 'FROM `' . $matches[1] . '`';
            }, preg_replace_callback('/JOIN\s+`(.+?)`/ms', function($matches){
                global $wpdb;
                if(!(mb_strpos($matches[1], $wpdb -> prefix) === 0)) $matches[1] = $wpdb -> prefix . $matches[1];
                return 'JOIN `' . $matches[1] . '`';
            }, $sql));
            $debugConsole -> log($sql);
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!$mysqli -> connect_errno){
                $res = [];
                $result = $mysqli -> query($sql);
                if($result){
                    while($res[] = $result -> fetch_assoc()){/*like a null loop*/}
                    array_pop($res);
                    $debugConsole -> log($res);
                    return $res;
                } else $debugConsole -> error('Cannon get result: ' . $result);
            } else $debugConsole -> error('DB Connection error ' . $mysqli -> connect_errno);
            return false;
        }
    }

    staticGlobals::init();
?>