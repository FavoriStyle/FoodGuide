<?php

    class API{
        private $debugConsole = false;
        private function mysql_result($query){
            $callback = function($matches){
                global $wpdb;
                if(!(mb_strpos($matches[2], $wpdb -> prefix) === 0)) $matches[2] = $wpdb -> prefix . $matches[2];
                return $matches[1] . ' `' . $matches[2] . '`';
            };
            $query = preg_replace_callback('/(FROM|JOIN|INTO)\s+`(.+?)`/ms', $callback, $query);
            $this -> debugConsole -> log($query);
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!$mysqli -> connect_errno){
                $res = [];
                $result = $mysqli -> query($query);
                if($result){
                    while($res[] = $result -> fetch_assoc()){/*like a null loop*/}
                    array_pop($res);
                    $this -> debugConsole -> log($res);
                    return $res;
                } else $this -> debugConsole -> error('Cannon get result: ' . $result);
            } else $this -> debugConsole -> error('DB Connection error ' . $mysqli -> connect_errno);
            return false;
        }
        private function utf8($str){
            return iconv(mb_detect_encoding($str, mb_detect_order(), true), "UTF-8", $str);
        }
        public function __construct($debugConsole = false){
            if (!$debugConsole){
                $this -> debugConsole = new class{
                    public function log($a){}
                    public function warn($a){}
                    public function error($a){}
                };
            }
        }
        public function update_singles($array){
            $sql = '';
            foreach ($array as $key => $value){
                $sql .= 'REPLACE INTO `categories_singles` (category, single) VALUES (FROM_BASE64(\'' . base64_encode($key) . '\'), FROM_BASE64(\'' . base64_encode($value) . '\')); ';
            }
            $this -> mysql_result($sql);
            die('{"state":"done"}');
        }
    }

    if (preg_match('/addons\/apiv4pjs\/?\?.+/', $_SERVER['REQUEST_URI'])){
        $API = new API;
        if(isset($_GET['act'])){
            if($_GET['act'] == 'save-singles'){
                $API -> update_singles($_POST);
            }
        }
    }
?>