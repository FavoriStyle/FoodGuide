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
        public function translate($from, $to, $subject){
            $str = file_get_contents('https://translate.yandex.net/api/v1.5/tr.json/translate?key=' . Secrets::$yandex_translate_api_key . '&text=' . urlencode($subject) . "&lang=$from-$to");
            if($str){
                $str = json_decode($str, true);
                if($str && $str['code'] == 200 && $str['text'] && $str['text'][0]){
                    return $str['text'][0];
                }
            }
            return $subject;
        }
        public function tel_count_incr($number){
            if(preg_match('/^\\+\\d{12}$/', $number)) $this -> mysql_result('INSERT INTO `tel_analytics` (number, `count`) VALUES (\'' . $number . '\', 1) ON DUPLICATE KEY UPDATE `count` = `count` + 1');
        }
        public function tel_count(){
            return $this -> mysql_result('SELECT SUM(`count`) FROM `tel_analytics`')[0]['SUM(`count`)'] * 1;
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
            } elseif($_GET['act'] == 'translate'){
                $avail_langs = [
                    'ru'    => 'ru',
                    'rus'   => 'ru',
                    'RUS'   => 'ru',
                    'RU'    => 'ru',
                    'ru-RU' => 'ru',
                    'ru_RU' => 'ru',
                    'uk'    => 'uk',
                    'ukr'   => 'uk',
                    'ua'    => 'uk',
                    'UKR'   => 'uk',
                    'UK'    => 'uk',
                    'UA'    => 'uk',
                    'ua-RU' => 'uk',
                    'ua_RU' => 'uk',
                    'uk-RU' => 'uk',
                    'uk_RU' => 'uk',
                    'ua-UA' => 'uk',
                    'uk-UA' => 'uk',
                    'en'    => 'en',
                    'eng'   => 'en',
                    'EN'    => 'en',
                    'ENG'   => 'en',
                    'en_US' => 'en',
                    'en-US' => 'en',
                    'en_UK' => 'en',
                    'en-UK' => 'en',
                ];
                if ($_GET['from'] && $_GET['to'] && $_REQUEST['subject'] && isset($avail_langs[$_GET['from']]) && isset($avail_langs[$_GET['to']])){
                    die('{"translated": true, "result": ' . json_encode($API -> translate($avail_langs[$_GET['from']], $avail_langs[$_GET['to']], $_REQUEST['subject'])) . '}');
                }
            } elseif ($_GET['act'] == 'telephone_counter'){
                $API -> tel_count_incr($_GET['number']);
                die();
            } elseif ($_GET['act'] == 'telephone_counter_all'){
                staticGlobals::mail('backender@favoristyle.com', 'FoodGuide: Telephone clicks analytics', 'Звіт за кількістю натискань на телефонні номери на сайті <a href="https://foodguide.in.ua">FoodGuide</a>:<br/>Кількість натискань за весь час: ' . $API -> tel_count());
                die();
            }
        }
    }
?>