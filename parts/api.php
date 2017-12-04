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
        private function tel_count(){
            return $this -> mysql_result('SELECT SUM(`count`) FROM `tel_analytics`')[0]['SUM(`count`)'] * 1;
        }
        public function mail_tel_count($lang = 'uk', $address = 'it.styles88@gmail.com'){
            $dic = (function($dic, $lang){
                $res = [];
                foreach ($dic as $phrase => $translates){
                    if (isset($translates[$lang])) $res[$phrase] = $translates[$lang]; else $res[$phrase] = $phrase;
                }
                return $res;
            })([
                'Number of clicks on phone numbers\'s report on the site {[sitename]}' => [
                    'ru' => 'Отчёт по кол-ву нажатий на телефонные номера на сайте {[sitename]}',
                    'uk' => 'Звіт за кількістю натискань на телефонні номери на сайті {[sitename]}',
                ],
                'Clicks count (by all the time)' => [
                    'ru' => 'Кол-во нажатий за всё время',
                    'uk' => 'Кількість натискань за весь час',
                ],
                'FoodGuide' => [], // имя сайта. Везде одинаковое, можно не переводить вовсе
                'FoodGuide: Telephone clicks analytics' => [
                    'ru' => 'FoodGuide: Аналитика кликов на телефонные номера',
                    'uk' => 'FoodGuide: Аналітика кліків на телефонні номери',
                ],
            ], $lang);
            staticGlobals::mail($address, $dic['FoodGuide: Telephone clicks analytics'], str_replace('{[sitename]}', '<a href="https://foodguide.in.ua/{[lang_suffix]}">' . $dic['FoodGuide'] . '</a>' , $dic['Number of clicks on phone numbers\'s report on the site {[sitename]}']) . ':<br/>' . $dic['Clicks count (by all the time)'] . ': ' . $this -> tel_count(), $lang);
        }
        public function get_items_and_tels(){
            $a = [];
            foreach ($this -> mysql_result('SELECT posts.post_title, meta_value FROM `posts` AS posts JOIN `postmeta` AS postmeta WHERE posts.post_type = "ait-item" AND postmeta.meta_key = "_ait-item_item-data" AND posts.ID = postmeta.post_id') as $row) {
                (function($b, $title) use (&$a){
                    if($b && 
                        preg_match('/Суми/', $b['map']['address'])
                    )
                    $a[] = [
                        'title' => $title,
                        'telephones' => (function($main_tel, $arr){
                            $main_tel = [$main_tel];
                            if ($arr){
                                foreach($arr as $tel){
                                    array_push($main_tel, $tel['number']);
                                }
                            }
                            return $main_tel;
                        })($b['telephone'], $b['telephoneAdditional']),
                        'address' => $b['map']['address']
                    ];
                })(unserialize(staticGlobals::utf8($row['meta_value'])), staticGlobals::utf8($row['post_title']));
            }
            return $a;
        }
        public function get_simp_lang_code($lang){
            return [
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
            ][$lang];
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
            foreach ([

                // Методы

                'save-singles' => function () use ($API){
                    $API -> update_singles((function($a){
                        $tmp = [];
                        foreach($a as $key => $value){
                            $tmp[str_replace('_', ' ', $key)] = $value;
                        }
                        return $tmp;
                    })($_POST));
                },
                'translate' => function () use ($API){
                    if ($_GET['from'] && $_GET['to'] && $_REQUEST['subject'] && $API -> get_simp_lang_code($_GET['from']) && $API -> get_simp_lang_code($_GET['to'])){
                        return '{"translated": true, "result": ' . json_encode($API -> translate($avail_langs[$_GET['from']], $avail_langs[$_GET['to']], $_REQUEST['subject'])) . '}';
                    }
                },
                'telephone_counter' => function () use ($API){
                    $API -> tel_count_incr($_GET['number']);
                },
                'telephone_counter_all' => function () use ($API){
                    $API -> mail_tel_count();
                },
                'non-unique-items' => function () use ($API){
                    return json_encode(eaDB::get_ids_not_unique_items());
                },
                'get_items_and_tels' => function () use ($API){
                    $res = '<table><thead><tr><td>Назва</td><td>Телефони</td><td>Адреса</td></tr></thead><tbody>';
                    foreach($API -> get_items_and_tels() as $item){
                        $res .= "<tr><td>$item[title]</td><td>" . implode('<br/>', $item['telephones']) . "</td><td>$item[address]</td>";
                    }
                    return $res . '</tbody><table>';
                },
                
                // Пока хватит
                
            ] as $act => $func){
                if ($_GET['act'] == $act) die($func());
            }
        }
    }
?>