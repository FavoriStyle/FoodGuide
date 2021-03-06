<?php
    class eaDB{
        private static $translates_cache = [];
        private static function getTaxsLangsIds($tax, $filter = false){
            $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => false]);
            if(is_wp_error($terms)){
                return $terms -> get_error_message();
            }
            foreach ($terms as $i => $value){
                $query_result = staticGlobals::mysql_result('SELECT `description` FROM `term_taxonomy` WHERE `taxonomy` = \'term_translations\' AND `description` LIKE \'%";i:' . $value -> term_id . ';%\'');
                if ($query_result){
                    $a = unserialize($query_result[0]['description']);
                    $terms[$i] = [
                        ($value -> term_id == $a['en']) ? $value : get_term_by('id', $a['en'], $tax),
                        ($value -> term_id == $a['ru']) ? $value : get_term_by('id', $a['ru'], $tax),
                        ($value -> term_id == $a['uk']) ? $value : get_term_by('id', $a['uk'], $tax),
                    ];
                } else unset($terms[$i]);
            }
            if ($filter) return $filter($terms);
            return $terms;
        }
        private static function categoriesOrganizer($obj, $parent_id = 0){
            $tmp_obj = new stdClass();
            foreach ($obj as $name => $category){
                if($category -> parent === $parent_id){
                    $tmp_obj -> $name = $category;
                }
            }
            foreach ($obj as $name => $category){
                foreach ($tmp_obj as $tmp_obj_name => $tmp_obj_category){
                    if ($tmp_obj_category -> id == $category -> parent){
                        $tmp_obj -> $tmp_obj_name -> childs = self::categoriesOrganizer($obj, $category -> parent);
                    }
                }
            }
            return $tmp_obj;
        }
        public static function getFilters(){
            $filter_list = self::getTaxsLangsIds('ait-items_filters', function($terms){
                foreach ($terms as $i => $value){
                    foreach ($value as $i1 => $value1){
                        $terms[$i][$i1] -> icon = get_option('ait-items_filters_category_' . $value1 -> term_id)["icon"];
                        if ($terms[$i][$i1] -> icon == '') $terms[$i][$i1] -> icon = '/wp-content/themes/foodguide/design/img/check.png';
                    }
                }
                return $terms;
            });
            $obj = new stdClass();
            $lang_index = _x('0', 'ea_pages_new [lang index]', 'ait-admin') * 1;
            foreach ($filter_list as $filter){
                $obj2 = new stdClass();
                $obj2 -> id = $filter[0] -> term_id;
                $obj2 -> icon = $filter[$lang_index] -> icon;
                $name = $filter[$lang_index] -> name;
                $obj -> $name = $obj2;
            }
            return json_encode($obj);
        }
        public static function getCategories($lang_index = false, $default_lang_index = 0){
            $cat_list = self::getTaxsLangsIds('ait-items');
            $obj = new stdClass();
            if ($lang_index === false) $lang_index = _x('0', 'ea_pages_new [lang index]', 'ait-admin') * 1;
            foreach ($cat_list as $category){
                $obj2 = new stdClass();
                $obj2 -> id = $category[$default_lang_index] -> term_id;
                $obj2 -> parent = $category[$default_lang_index] -> parent;
                $name = $category[$lang_index] -> name;
                $obj -> $name = $obj2;
            }
            foreach ($obj as $name => $category){
                $obj -> $name -> childs = new stdClass();
            }
            return json_encode(self::categoriesOrganizer($obj));
        }
        public static function translate($phrase, $lang){
            if (!isset(self::$translates_cache[$phrase]) || !isset(self::$translates_cache[$phrase][$lang])){
                $res = staticGlobals::mysql_result('SELECT ' . $lang . ' FROM `custom_translates` WHERE string = FROM_BASE64("' . base64_encode($phrase) . '")');
                if ($res && $res[0] && $res[0][$lang]) self::$translates_cache[$phrase][$lang] = $res[0][$lang]; else self::$translates_cache[$phrase][$lang] = $phrase;
            }
            return self::$translates_cache[$phrase][$lang];
        }
        public static function categoryToSingle($cat, $case_mode = 1){
            $cat = staticGlobals::do_case($cat, 1);
            $a = staticGlobals::mysql_result('SELECT `single` FROM `categories_singles` WHERE `category` = FROM_BASE64(\'' . base64_encode($cat) . '\')');
            if ($a) return staticGlobals::do_case(staticGlobals::utf8($a[0]['single']), $case_mode); else return staticGlobals::do_case($cat, $case_mode);
        }
        public static function get_ids_not_unique_items(){
            $a = staticGlobals::mysql_result('SELECT posts1.ID FROM `posts` AS posts1 JOIN `posts` AS posts2 WHERE posts1.post_title = posts2.post_title AND posts1.ID != posts2.ID AND posts1.post_type = \'ait-item\' AND posts2.post_type = posts1.post_type AND 0 NOT IN (SELECT parent FROM `term_taxonomy` AS taxonomy WHERE taxonomy.taxonomy = \'post_translations\' AND taxonomy.description LIKE CONCAT(\'%:\', posts1.ID, \';%\') AND taxonomy.description LIKE CONCAT(\'%:\', posts2.ID, \';%\'))');
            if($a){
                $b = [];
                foreach ($a as $row){
                    $b[] = $row['ID'] * 1;
                }
                return $b;
            }
            return $a;
        }
    }

    class staticGlobals{
        private static $debugConsole = null;
        public static function init(){
            self::$debugConsole = new class{
                public function log($a){}
                public function warn($a){}
                public function error($a){}
            };
        }
        public static function do_case($res, $case_mode){
            if ($case_mode == 2) return mb_strtoupper($res); elseif ($case_mode == 1) return mb_strtoupper(mb_substr($res, 0, 1)) . mb_strtolower(mb_substr($res, 1)); else return mb_strtolower($res);
        }
        public static function utf8($str){
            return iconv(mb_detect_encoding($str, mb_detect_order(), true), "UTF-8", $str);
        }
        public static function mysql_prefix(){
            global $wpdb;
            if ($wpdb) return $wpdb -> prefix;
        }
        public static function mysql_result($sql, $debugConsole = false){
            if (!$debugConsole) $debugConsole = self::$debugConsole;
            $sql = preg_replace_callback('/(FROM|JOIN|INTO|UPDATE)\s+`(.+?)`/ms', function($matches){
                global $wpdb;
                if(!(mb_strpos($matches[2], $wpdb -> prefix) === 0)) $matches[2] = $wpdb -> prefix . $matches[2];
                return $matches[1] . ' `' . $matches[2] . '`';
            }, $sql);
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
                } else $debugConsole -> warn('Cannon get result. This is normal for non-resultative queries');
            } else $debugConsole -> error('DB Connection error ' . $mysqli -> connect_errno);
            return false;
        }
        public static function mail($mail, $subject, $html, $wrapper_lang = 'uk'){
            $dic = (function($dic, $lang){
                $res = [];
                foreach ($dic as $phrase => $translates){
                    if (isset($translates[$lang])) $res[$phrase] = $translates[$lang]; else $res[$phrase] = $phrase;
                }
                return $res;
            })([
                'FoodGuide – the most complete catalogue of cafes and restaurants of Ukraine' => [
                    'ru' => 'FoodGuide – самый полный каталог кафе и ресторанов Украины',
                    'uk' => 'FoodGuide – найповніший каталог кафе і ресторанів України',
                ],
                '© [year]. ALL RIGHTS RESERVED.' => (function($y){
                    $c = date('Y');
                    $y .= (($y != $c) ? '-' . $c : '');
                    return [
                        'en' => "© $y. ALL RIGHTS RESERVED.",
                        'ru' => "© $y. ВСЕ ПРАВА ЗАЩИЩЕНЫ.",
                        'uk' => "© $y. УСІ ПРАВА ЗАХИЩЕНО.",
                    ];
                })(2017),
                'lang_suffix' => [
                    'en' => 'en/',
                    'ru' => 'ru/',
                    'uk' => '',
                ],
            ], $wrapper_lang);
            mail(
                $mail,
                $subject,
                '<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet"><div style="margin: 0; font-family: \'Open Sans\', sans-serif; color: #000; font-weight: bold; font-style: normal; padding: 0; border: 0; font-size: 14px; vertical-align: baseline;">
                <a href="https://foodguide.in.ua/' . $dic['lang_suffix'] . '"><div style="height: 82px; background-color: #825bae; background-image: url(\'https://foodguide.in.ua/wp-content/themes/foodguide/design/img/logo.png\'); background-repeat: no-repeat; background-position: top left; background-size: contain;">&nbsp;</div></a>
                <div style="padding: 20px;">' . str_replace('{[lang_suffix]}', $dic['lang_suffix'], $html) . '</div>
                <div style="line-height: 4px; background-color: #38343f; color: #fff; padding-left: 20px; position: fixed; bottom: 0; left: 0; width: 100%;">
                <p>&nbsp;</p>
                <p><br/>' . $dic['FoodGuide – the most complete encyclopedia of cafes and restaurants of Ukraine.'] . '</p>
                <p style="text-align: center;"><strong>' . $dic['© [year]. ALL RIGHTS RESERVED.'] . '</strong></p>
                <p>&nbsp;</p>
                </div>
                </div>',
                    "MIME-Version: 1.0\r\n" .
                    "Content-type: text/html; charset=UTF-8\r\n"
            );
        }
        public static function getCurrentGitHubRelease(){
            $latest_release = OpenpartsCache::cache('gh_latest_release');
            if (!$latest_release || $latest_release == 'latest'){
                $latest_release = (function() use ($settings){
                    ob_start();
                    echo '$settings = ';
                    var_dump($settings);
                    $result = file_get_contents('https://api.github.com/graphql', false, stream_context_create([
                        'http' => [
                            'header'  => "User-Agent: FoodGuide server-side API/0.1\r\nAuthorization: bearer " . Secrets::$github_graphql_token . "\r\n", // используем токен ограниченой функциональности. Ничего не умеет, ничего не знает... он просто есть, ибо требование
                            'method'  => 'POST',
                            'content' => json_encode([
                                'query' => '{repository(owner:' . json_encode($settings['user']) . ',name:' . json_encode($settings['repo']) . '){releases(last:1){edges{node{tag{name}}}}}}'
                            ])
                        ]
                    ]));
                    echo '$result = ';
                    var_dump($result);
                    OpenpartsCache::cache('gh_debug_info', ob_get_clean());
                    if ($result && ($result = json_decode($result, true)) && !isset($result['errors'])){
                        return $result['data']['repository']['releases']['edges'][0]['node']['tag']['name'];
                    }
                    return 'latest'; // emergency mode!!!
                })();
                OpenpartsCache::cache('gh_latest_release', $latest_release);
            }
            return $latest_release;
        }
    }

    staticGlobals::init();
?>