<?php

    class API{
        private $mysql_result = null;
        public function __construct($debugConsole = false){
            if (!$debugConsole){
                $debugConsole = new class{
                    public function log($a){}
                    public function warn($a){}
                    public function error($a){}
                };
            }
            $this -> mysql_result = function($sql) use ($debugConsole){
                $callback = function($matches){
                    global $wpdb;
                    if(!(mb_strpos($matches[2], $wpdb -> prefix) === 0)) $matches[2] = $wpdb -> prefix . $matches[2];
                    return $matches[1] . ' `' . $matches[2] . '`';
                };
                $sql = preg_replace_callback('/FROM\s+`(.+?)`/ms', function($matches){
                    global $wpdb;
                    if(!(mb_strpos($matches[1], $wpdb -> prefix) === 0)) $matches[1] = $wpdb -> prefix . $matches[1];
                    return 'FROM `' . $matches[1] . '`';
                }, preg_replace_callback('/JOIN\s+`(.+?)`/ms', function($matches){
                    global $wpdb;
                    if(!(mb_strpos($matches[1], $wpdb -> prefix) === 0)) $matches[1] = $wpdb -> prefix . $matches[1];
                    return 'JOIN `' . $matches[1] . '`';
                }, preg_replace_callback('/INTO\s+`(.+?)`/ms', function($matches){
                    global $wpdb;
                    if(!(mb_strpos($matches[1], $wpdb -> prefix) === 0)) $matches[1] = $wpdb -> prefix . $matches[1];
                    return 'JOIN `' . $matches[1] . '`';
                }, $sql)));
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
            };
        }
        private function utf8($str){
            return iconv(mb_detect_encoding($str, mb_detect_order(), true), "UTF-8", $str);
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