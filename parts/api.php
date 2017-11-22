<?php

    class API{
        private $debugConsole = false;
        private function mysql_result($query){
            return staticGlobals::mysql_result($query, $this -> debugConsole);
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
            } else $this -> debugConsole = $debugConsole;
        }
        public function update_singles($array){
            foreach ($array as $key => $value){
                $this -> mysql_result('REPLACE INTO `categories_singles` (category, single) VALUES (FROM_BASE64(\'' . base64_encode($key) . '\'), FROM_BASE64(\'' . base64_encode($value) . '\'))');
            }
            die('{"state":"done"}');
        }
    }

    if (preg_match('/addons\/apiv4pjs\/?\?.+/', $_SERVER['REQUEST_URI'])){
        $API = new API(new class{
            public function log($a){
                //var_dump($a);
            }
            public function warn($a){
                //var_dump($a);
            }
            public function error($a){
                //var_dump($a);
            }
        });
        if(isset($_GET['act'])){
            if($_GET['act'] == 'save-singles'){
                $API -> update_singles((function($a){
                    $tmp = [];
                    foreach($a as $key => $value){
                        $tmp[str_replace('_', ' ', $key)] = $value;
                    }
                    return $tmp;
                })($_POST));
            }
        }
    }
?>