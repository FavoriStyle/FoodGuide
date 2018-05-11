<?php

    require($_SERVER['DOCUMENT_ROOT'] . '/addons/composer-modules/vendor/autoload.php');
    use Location\Coordinate;
    use Location\Distance\Vincenty;

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
            return '{"state":"done"}';
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
        public function locateMe($IP){
            $str = file_get_contents('https://allbooms.com:3008/?act=getIPinfo&token=' . Secrets::$gogs_token . '&params=' . urlencode(json_encode([
                'ip' => $IP
            ])));
            if($str){
                $str = json_decode($str, true);
                if($str && $str['type'] == 'success'){
                    if(!$str['mobile'] && $str['city']) return $str['city']; elseif($str['country']) return $str['country'];
                }
            }
            return 'UA';
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
        private static $_cat_names_temp_db = []; // Временная БД для названий категорий. Дабы по сто раз не запрашивать и не конвертировать
        public function append_categories($item){
            $post_terms = (function() use ($item){
                $sql = 'SELECT `term_taxonomy_id` FROM `' . staticGlobals::mysql_prefix() . 'term_relationships` WHERE';
                append_sql($sql, [$item['post_id']], 'object_id', false);
                $res = [];
                $term_list = get_mysql_result($sql);
                foreach($term_list as $term){
                    $res[] = $term['term_taxonomy_id'];
                }
                return $res;
            })();
            $item['categories'] = (function () use ($post_terms){
                $sql = 'SELECT `term_id` FROM `' . staticGlobals::mysql_prefix() . 'term_taxonomy` WHERE';
                append_sql($sql, ['ait-items'], 'taxonomy');
                append_sql($sql, $post_terms, 'term_id', false);
                $res = [];
                $categories = get_mysql_result($sql);
                foreach($categories as $cat){
                    $res[] = $cat['term_id'];
                }
                return $res;
            })();
            foreach ($item['categories'] as $i => $cat_id){
                $item['categories'][$i] = (function() use ($cat_id){
                    if (!isset(self::$_cat_names_temp_db[$cat_id])){
                        $sql = 'SELECT `name` FROM `' . staticGlobals::mysql_prefix() . "terms` WHERE `term_id` = $cat_id";
                        self::$_cat_names_temp_db[$cat_id] = get_mysql_result($sql)[0]['name'];
                        self::$_cat_names_temp_db[$cat_id] = iconv(mb_detect_encoding(self::$_cat_names_temp_db[$cat_id], mb_detect_order(), true), "UTF-8", self::$_cat_names_temp_db[$cat_id]);
                    }
                    return self::$_cat_names_temp_db[$cat_id];
                })();
            }
            return $item;
        }
        public function get_posts_by_term_names($term_names, $lang){
            foreach($term_names as $i => $v){
                $term_names[$i] = base64_encode($v);
            }
            $lang = base64_encode(json_encode($lang));
            $stack = [];
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (!$mysqli -> connect_errno){
                $query_result = $mysqli -> query("SELECT `ID`
                FROM " . staticGlobals::mysql_prefix() . "posts AS posts
                JOIN " . staticGlobals::mysql_prefix() . "term_relationships AS rs
                JOIN " . staticGlobals::mysql_prefix() . "terms AS terms
                JOIN " . staticGlobals::mysql_prefix() . "term_taxonomy AS term_taxonomy
                WHERE " . (function() use ($term_names){
                    $str = '(';
                    foreach($term_names as $v){
                        $str .= "TO_BASE64(terms.name) = '$v' OR ";
                    }
                    return substr($str, 0, -4) . ')';
                })() . "
                AND rs.term_taxonomy_id = terms.term_id
                AND posts.ID = rs.object_id
                AND posts.post_type = 'ait-item'
                AND term_taxonomy.description LIKE CONCAT('%:', FROM_BASE64('$lang') ,';i:', terms.term_id, '%')");
                if($query_result) while($a = $query_result -> fetch_row()) $stack[] = (int) $a[0];
            }
            return $stack;
        }
        public function get_nearest_items($settings = []){
            function get_factical_distantion($point0, $point1){
                $point0 = new Coordinate($point0['lat'] * 1, $point0['lng'] * 1);
                $point1 = new Coordinate($point1['lat'] * 1, $point1['lng'] * 1);
                return (new Vincenty()) -> getDistance($point0, $point1);
            }
            function get_mysql_result($sql, $parse_meta = false){
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                if (!$mysqli -> connect_errno){
                    $query_result = $mysqli -> query($sql);
                    $res = [];
                    if ($query_result){
                        while ($res[] = (function () use (&$query_result, $parse_meta){
                            $assoc = $query_result -> fetch_assoc();
                            if ($assoc != null && $assoc['meta_value'] && $parse_meta){
                                $assoc['meta_value'] = iconv(mb_detect_encoding($assoc['meta_value'], mb_detect_order(), true), "UTF-8", $assoc['meta_value']);
                                $assoc['meta_value'] = unserialize($assoc['meta_value']);
                                $assoc['address'] = $assoc['meta_value']['map']['address'];
                                $assoc['lat'] = $assoc['meta_value']['map']['latitude'];
                                $assoc['lng'] = $assoc['meta_value']['map']['longitude'];
                                $assoc['phones'] = [$assoc['meta_value']['telephone']];
                                if ($assoc['meta_value']['telephoneAdditional'] != '') foreach($assoc['meta_value']['telephoneAdditional'] as $a){
                                    $assoc['phones'][] = $a['number'];
                                }
                                $assoc['thumbnail'] = /*
                                
                                исключение*: "трёхэтажный" запрос. Грёбаный вордпресс. Во первых, выбираем абсолютно "левый" мета-ключ, он не упоминается в значении meta_value, которое мы парсим.
                                             Во вторых, теперь, после выбора ключа, нужно узнать, куда в точности он указал. Для этого сделаем ещё один запрос, но уже в другую таблицу с постами.
                                             НАХЕРА ДЕЛАТЬ ИЗ КАРТИНКИ ЦЕЛЫЙ ОБЪЕКТ ПОСТА? НЕОПТИМАЛЬНОЕ ИСПОЛЬЗОВАНИЕ МАШИННЫХ РЕСУРСОВ МАЗАФАКА
                                             Всё, полегчало

                                * в каноническом понимании (из правила). То, что не подпадает под общее определение
                                
                                */ (function() use ($assoc){
                                    $thid = (function() use ($assoc){
                                        if ($query_result = $this -> mysql_result("SELECT `meta_value` FROM `postmeta` WHERE `post_id` = $assoc[post_id] AND `meta_key` = '_thumbnail_id'")) return $query_result[0]['meta_value'];
                                        return false;
                                    })();
                                    if (
                                        $thid &&
                                        $query_result = $this -> mysql_result("SELECT `guid` FROM `posts` WHERE `ID` = $thid")
                                    )
                                        return $query_result[0]['guid'];
                                    return false;
                                })();
                                unset($assoc['meta_value']);
                            }
                            return $assoc;
                        })()) /* а теперь внимание: */ {} // нулл-луп? неее. а почему?)
                        array_pop($res); // убираем последний нулл
                    }
                    return $res;
                }
                return false;
            }
            function append_sql(&$sql, $array, $sql_name, $append_AND = true){
                $append_AND = $append_AND ? ' AND' : '';
                $sql .= ' (';
                if ($array != []){
                    $sql .= "`$sql_name` = " . json_encode(array_shift($array));
                    foreach($array as $val){
                        $sql .= " OR `$sql_name` = " . json_encode($val);
                    }
                }
                $sql .= ")$append_AND";
            }
            $settings = array_merge([ //defaults, потом не нужно будет
                'lang' => 'en',
            ], $settings);
            $response = new stdClass();
            //получаем массив заведений
            $items = (function() use ($settings){
                $sql = 'SELECT `post_id`, `meta_value` FROM `' . staticGlobals::mysql_prefix() . 'postmeta` WHERE (';
                append_sql($sql, ['_ait-item_item-data'], 'meta_key', false);
                $sql .= ')' . ($settings['cat'] ? (function($cat, $lang){
                    $sql = ' AND `post_id` IN (';
                    foreach($this -> get_posts_by_term_names([$cat], $lang) as $item_id){
                        $sql .= "$item_id,";
                    }
                    return substr($sql, 0, -1) . ')';
                })($settings['cat'], $settings['lang']) : '');
                return get_mysql_result($sql, true);
            })();
            if (!$settings['cat']){
                //получаем массив с (строковыми) ключами, равными ид'ам заведений, и значениями, равными строковым представлениям языков
                $langs = (function(){
                    $sql = 'SELECT `description` FROM `' . staticGlobals::mysql_prefix() . 'term_taxonomy` WHERE';
                    append_sql($sql, ['post_translations'], 'taxonomy', false);
                    $langs = get_mysql_result($sql);
                    $res = [];
                    foreach($langs as $langs_single_item){
                        foreach(unserialize($langs_single_item['description']) as $lang => $id){
                            $res["$id"] = $lang;
                        }
                    }
                    return $res;
                })();
            }
            foreach($items as $i => $item){
                $items[$i]['distance'] = get_factical_distantion(
                    /*
                        $item
                    /*/
                    [ // так получилось быстрее на целых 15% (!) с воможной дельтой (1.(3)% или (4/3)%)
                        'lat' => $item['lat'],
                        'lng' => $item['lng']
                    ]
                    //*/
                    , $settings['geo']);
                if (!$settings['cat'] && $langs[$item['post_id']] != $settings['lang']){unset($items[$i]);}
            }
            $this -> debugConsole -> log($items, 'Перед превращением в плотный массив: $items = ');
            // превращаем в плотный нумерованный массив + сортируем по дистанции
            return (function() use ($items, $settings){ // внимательнее в этих местах   <<-----------
                $res = [];                                                                         //
                for ($i = 0; $i < $settings['count']; $i++){                                       //
                    (function() use (&$items, &$res){ // <<------------------------------------------
                        if ($items != []){
                            foreach($items as $index => $item){
                                if ((!isset($min) || $item['distance'] < $min['distance'])) $min = ['distance' => $item['distance'], 'index' => $index];
                            }
                            $res[] = $items[$min['index']];
                            unset($items[$min['index']]);
                        }
                    })();
                }
                foreach($res as $i => $item){
                    $res[$i] = $this -> append_categories($item);
                    $post_obj = (array) get_post($item['post_id']);
                    $res[$i]['title'] = $post_obj['post_title'];
                    $res[$i]['link'] = $post_obj['guid'];
                }
                return $res;
            })();
        }
    }

    if (preg_match('/addons\/apiv4pjs\/?\?.+/', $_SERVER['REQUEST_URI'])){
        $API = new API(new class{
            public function log($a, $b = ''){
                echo $b;
                var_dump($a);
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
                    return $API -> update_singles((function($a){
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
                    require_once($_SERVER['DOCUMENT_ROOT'] . '/addons/node_notifier/autoload.php'); 
                    Node_notifier::throwEvent("Клик на телефон");
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
                'locateMe' => function () use ($API){
                    return json_encode($API -> locateMe($_SERVER['REMOTE_ADDR']));
                },
                'get_nearest_items_beta' => function () use ($API){
                    var_dump($_GET);
                    $resp = new stdClass();
                    $resp -> type = 'error';
                    if (!isset($_GET['geo'])){
                        $resp -> message = 'Can\'t load user geolocation';
                        return json_encode($resp);
                    }
                    if (!isset($_GET['count'])){
                        $_GET['count'] = 5;
                    } else {
                        $_GET['count'] *= 1;
                    }
                    if (!isset($_GET['lang'])){
                        $resp -> message = 'Can\'t get lang';
                        $resp -> stack = "Error in main:\n\tCan't get language to specify an array";
                        return json_encode($resp);
                    }
                    $_GET['geo'] = json_decode($_GET['geo'], true);
                    $_GET['post_type'] = json_decode($_GET['post_type']);
                    if (!$_GET['geo'] || !isset($_GET['geo']['lat']) || !isset($_GET['geo']['lng'])){
                        $resp -> message = 'Can\'t load user geolocation';
                        $resp -> stack = $_GET['geo'];
                        return json_encode($resp);
                    }
                    $resp -> type = 'success';
                    $resp -> responce = $API -> get_nearest_items([
                        'lang' => $_GET['lang'],
                        'geo' => $_GET['geo'],
                        'count' => $_GET['count'],
                        'cat' => isset($_GET['cat']) ? $_GET['cat'] : null,
                    ]);
                    return json_encode($resp);
                },
                'N!event' => function(){
                    require_once($_SERVER['DOCUMENT_ROOT'] . '/addons/node_notifier/autoload.php');
                    Node_notifier::throwEvent(base64_decode($_GET['N!ev_name']), $_GET['N!ev_data'] ? json_decode(base64_decode($_GET['N!ev_data']), true) : []);
                    ob_start();
                    var_dump(get_current_user_id());
                    return [
                        response => ob_get_clean()
                    ];
                },
                
                // Пока хватит
                
            ] as $act => $func){
                if ($_GET['act'] == $act) die($func());
            }
        }
    }
?>