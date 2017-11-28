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
        public static function mysql_result($sql, $debugConsole = false){
            if (!$debugConsole) $debugConsole = self::$debugConsole;
            $sql = preg_replace_callback('/(FROM|JOIN|INTO)\s+`(.+?)`/ms', function($matches){
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
            })([
                'FoodGuide – the most complete</p><p>encyclopedia of cafes and restaurants</p><p>of Ukraine.' => [
                    'ru' => 'FoodGuide – самая полная</p><p>энциклопедия кафе и ресторанов</p><p>Украины.',
                    'uk' => 'FoodGuide – найповніша</p><p>енциклопедія кафе і ресторанів</p><p>України.',
                ],
                '© [year]. ALL RIGHTS RESERVED.' => [
                    'en' => '© 2017. ALL RIGHTS RESERVED.',
                    'ru' => '© 2017. ВСЕ ПРАВА ЗАЩИЩЕНЫ.',
                    'uk' => '© 2017. УСІ ПРАВА ЗАХИЩЕНО.',
                ],
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
                <a href="https://foodguide.in.ua/' . $dic['lang_suffix'] . '"><div style="height: 82px; background-color: #825bae; background-image: url(\'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj4KPCEtLSBDcmVhdG9yOiBDb3JlbERSQVcgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWw6c3BhY2U9InByZXNlcnZlIiB3aWR0aD0iNzAwbW0iIGhlaWdodD0iMjIxbW0iIHN0eWxlPSJzaGFwZS1yZW5kZXJpbmc6Z2VvbWV0cmljUHJlY2lzaW9uOyB0ZXh0LXJlbmRlcmluZzpnZW9tZXRyaWNQcmVjaXNpb247IGltYWdlLXJlbmRlcmluZzpvcHRpbWl6ZVF1YWxpdHk7IGZpbGwtcnVsZTpldmVub2RkOyBjbGlwLXJ1bGU6ZXZlbm9kZCIKdmlld0JveD0iMCAwIDcwMCAyMjEiCiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiA8ZGVmcz4KICA8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgogICA8IVtDREFUQVsKICAgIC5maWwwIHtmaWxsOnJnYmEoMCwwLDAsMC4wNyl9CiAgICAuZmlsMSB7ZmlsbDp3aGl0ZX0KICAgIC5maWwyIHtmaWxsOndoaXRlO2ZpbGwtcnVsZTpub256ZXJvfQogICBdXT4KICA8L3N0eWxlPgogPC9kZWZzPgogPGcgaWQ9IkxheWVyX3gwMDIwXzEiPgogIDxtZXRhZGF0YSBpZD0iQ29yZWxDb3JwSURfMENvcmVsLUxheWVyIi8+CiAgPHJlY3QgY2xhc3M9ImZpbDAiIHk9IjguNDkxN2UtMDA1IiB3aWR0aD0iMjIxLjcyOSIgaGVpZ2h0PSIyMjEiLz4KICA8ZyBpZD0iXzEyNzY3MDczNiI+CiAgIDxwYXRoIGlkPSJfMTI3NjcyODk2IiBjbGFzcz0iZmlsMSIgZD0iTTE0MS4zOSAxNDYuNTI1YzAuMzU3NSwtMC43NjU0IDAuNDQ5NSwtMS42MzA2IDAuNDQ5NSwtMi40NjkxIDAsLTIzLjI2OTggLTAuMDM2NCwtNDYuNTM5NSAtMC4wMzY0LC02OS44MTA1IDAsLTMuNTkwMiAtMS44NDczLC00LjQ4MDcgLTUuMTkyNiwtNC4zNzEyIC05LjIxOTMsMC4zMDY0IC0xOC45MzYyLDYuNDcwMiAtMTguOTM2MiwxNi42NTU4bDAgMzAuMDcxN2MwLDAuNjQwMiAxLjA1OTUsMS40MDc2IDEuODcyLDEuNDA3Nmw4LjExMjUgMGMwLDguNjgyMiAtMC4wMjQ3LDE3LjM2MzcgLTAuMDI0NywyNi4wNDY2IDAsMy44OTM3IDMuMTk1Nyw3LjA3OSA3LjEwMjQsNy4wNzlsMC4wMDA2IDBjMy4wMzcyLDAgNS42NDI4LC0xLjkyMzMgNi42NTI5LC00LjYwOTl6Ii8+CiAgIDxwYXRoIGlkPSJfMTI3NjcwMjMyIiBjbGFzcz0iZmlsMSIgZD0iTTc5Ljg4OTkgMTAxLjEyNmMwLDIuMTA1MiA1LjU0MTIsNi42MjY5IDcuNjUzNCw3LjA5ODEgMCwxMS45NDQgLTAuMDE3OSwyMy44ODc0IC0wLjAxNzksMzUuODMxNCAwLDAuMTcwOSAwLjAwNTUsMC4zMzg1IDAuMDE3OSwwLjUwNjhsMCAwLjA0MTdjMCwwLjkzMDIgMC4yMzI2LDEuNjc2NCAwLjU5MTUsMi4zMTMyIDEuMTA4MiwyLjQ3ODYgMy42MDc0LDQuMjE3MyA2LjQ5Myw0LjIxNzNsMC4wMDA3IDBjMy43NTU2LDAgNi44NTMyLC0yLjk0NTkgNy4wODY1LC02LjYzMzggMC4zMzAxLC0yLjM0MzkgMC4xMDcxLC01LjI1MDcgMC4xMDcxLC03Ljc0MDQgMCwtMTIuNDU0OSAwLC0yNi45Mzc4IDAsLTI4LjUzNjIgMS45NDYxLC0wLjk4NjIgNy43MDk3LC00Ljc1MzUgNy43MDk3LC02LjY5NmwwIC0yNi4yOTg5IC0wLjAwNDIgMCAwIC0yLjA3NjVjMCwtMS42MjAzIC0xLjMzMjYsLTIuOTQ0NSAtMi45NjMxLC0yLjk0NDVsMCAwYy0xLjYzMTEsMCAtMi45NjM3LDEuMzI0OCAtMi45NjM3LDIuOTQ0NWwwIDIuMDc2NSAtMC4wMDA3IDAgMCAxNi4xNTM4YzAsMS42MjAzIC0xLjMzMzMsMi45NDQ0IC0yLjk2MzEsMi45NDQ0bDAgMGMtMS42MzExLDAgLTIuOTYzOCwtMS4zMjQ4IC0yLjk2MzgsLTIuOTQ0NGwwIC0xNi4xNTM4IC0wLjAwMDcgMCAwIC0yLjA3NjVjMCwtMS42MjAzIC0xLjMzMzMsLTIuOTQ0NSAtMi45NjMxLC0yLjk0NDVsMCAwYy0xLjYzMTEsMCAtMi45NjQ0LDEuMzI0OCAtMi45NjQ0LDIuOTQ0NWwwIDIuMDc2NSAtMC4wMDA3IDAgMCAxNi4xNTM4YzAsMS42MjAzIC0xLjMzMjYsMi45NDQ0IC0yLjk2MzEsMi45NDQ0bDAgMGMtMS42MzA1LDAgLTIuOTYzOCwtMS4zMjQ4IC0yLjk2MzgsLTIuOTQ0NGwwIC0xNi4xNTM4IC0wLjAwMDcgMCAwIC0yLjA3NjVjMCwtMS42MjAzIC0xLjMzMjYsLTIuOTQ0NSAtMi45NjMxLC0yLjk0NDVsMCAwYy0xLjYzMTEsMCAtMi45NjM3LDEuMzI0OCAtMi45NjM3LDIuOTQ0NWwwIDIuMDc2NSAwIDE2LjE1MzggMCAwLjA3NTkgMCA5LjY2NzF6Ii8+CiAgPC9nPgogIDxwYXRoIGNsYXNzPSJmaWwyIiBkPSJNMzM4LjUxMiAxMTYuOTY1bC0yMy44NDMzIDAgMCAyMy44OTAxIC0xMy44NjIgMCAwIC01OS4zNTY1IDQxLjg3OTMgMCAwIDEwLjYwMjQgLTI4LjAxNzMgMCAwIDE0LjI2MTcgMjMuODQzMyAwIDAgMTAuNjAyM3ptNy44NDU0IDEuNDMzMWMwLC02LjYzNjkgMS44ODQ2LC0xMi4wMzU0IDUuNjUzNywtMTYuMjA5NiAzLjc1NTIsLTQuMTc0IDkuMDA0LC02LjI2MTIgMTUuNzQ2NiwtNi4yNjEyIDYuNzU2NiwwIDEyLjAxOTQsMi4wODcyIDE1Ljc4ODYsNi4yNjEyIDMuNzU1Miw0LjE3NDIgNS42Mzk3LDkuNTcyNyA1LjYzOTcsMTYuMjA5NmwwIDAuODQ4OGMwLDYuNjY0NyAtMS44ODQ1LDEyLjA3NzMgLTUuNjM5NywxNi4yMzc1IC0zLjc2OTIsNC4xNjAzIC05LjAwNDEsNi4yMzM0IC0xNS43MDQ4LDYuMjMzNCAtNi43OTg0LDAgLTEyLjA3NTIsLTIuMDczMSAtMTUuODMwNCwtNi4yMzM0IC0zLjc2OTEsLTQuMTYwMiAtNS42NTM3LC05LjU3MjggLTUuNjUzNywtMTYuMjM3NWwwIC0wLjg0ODh6bTEzLjgzNDEgMC44NDg4YzAsMy43MDExIDAuNTcyNCw2LjU5NTIgMS43MzEsOC43MTAxIDEuMTU4NywyLjEwMDkgMy4xMjcxLDMuMTU4NCA1LjkxOSwzLjE1ODQgMi42OTQzLDAgNC42MzQ2LC0xLjA1NzUgNS44MDcyLC0zLjE4NjIgMS4xNzI3LC0yLjExNSAxLjc1OSwtNS4wMDkgMS43NTksLTguNjgyM2wwIC0wLjg0ODhjMCwtMy41ODk3IC0wLjU4NjMsLTYuNDY5OSAtMS43NTksLTguNjI2NSAtMS4xNzI2LC0yLjE1NjYgLTMuMTQwOSwtMy4yNDE5IC01Ljg5MSwtMy4yNDE5IC0yLjczNjEsMCAtNC42NzY1LDEuMDg1MyAtNS44MzUyLDMuMjU1OCAtMS4xNTg2LDIuMTg0NSAtMS43MzEsNS4wNTA3IC0xLjczMSw4LjYxMjZsMCAwLjg0ODh6bTMzLjIzODIgLTAuODQ4OGMwLC02LjYzNjkgMS44ODQ2LC0xMi4wMzU0IDUuNjUzNywtMTYuMjA5NiAzLjc1NTIsLTQuMTc0IDkuMDA0LC02LjI2MTIgMTUuNzQ2NywtNi4yNjEyIDYuNzU2NSwwIDEyLjAxOTMsMi4wODcyIDE1Ljc4ODUsNi4yNjEyIDMuNzU1Miw0LjE3NDIgNS42Mzk3LDkuNTcyNyA1LjYzOTcsMTYuMjA5NmwwIDAuODQ4OGMwLDYuNjY0NyAtMS44ODQ1LDEyLjA3NzMgLTUuNjM5NywxNi4yMzc1IC0zLjc2OTIsNC4xNjAzIC05LjAwNDEsNi4yMzM0IC0xNS43MDQ4LDYuMjMzNCAtNi43OTg0LDAgLTEyLjA3NTIsLTIuMDczMSAtMTUuODMwNCwtNi4yMzM0IC0zLjc2OTEsLTQuMTYwMiAtNS42NTM3LC05LjU3MjggLTUuNjUzNywtMTYuMjM3NWwwIC0wLjg0ODh6bTEzLjgzNDEgMC44NDg4YzAsMy43MDExIDAuNTcyNCw2LjU5NTIgMS43MzEsOC43MTAxIDEuMTU4NywyLjEwMDkgMy4xMjcxLDMuMTU4NCA1LjkxOSwzLjE1ODQgMi42OTQzLDAgNC42MzQ2LC0xLjA1NzUgNS44MDczLC0zLjE4NjIgMS4xNzI2LC0yLjExNSAxLjc1ODksLTUuMDA5IDEuNzU4OSwtOC42ODIzbDAgLTAuODQ4OGMwLC0zLjU4OTcgLTAuNTg2MywtNi40Njk5IC0xLjc1ODksLTguNjI2NSAtMS4xNzI3LC0yLjE1NjYgLTMuMTQwOSwtMy4yNDE5IC01Ljg5MSwtMy4yNDE5IC0yLjczNjEsMCAtNC42NzY2LDEuMDg1MyAtNS44MzUzLDMuMjU1OCAtMS4xNTg2LDIuMTg0NSAtMS43MzEsNS4wNTA3IC0xLjczMSw4LjYxMjZsMCAwLjg0ODh6bTMzLjIzODMgLTAuMzYxOGMwLC02Ljg3MzQgMS41MDc2LC0xMi40MjUgNC41MjI5LC0xNi42NDA5IDMuMDE1MywtNC4yMTU5IDcuMjU5MSwtNi4zMTY5IDEyLjc0NTMsLTYuMzE2OSAyLjAzODEsMCAzLjg5NDgsMC40NzMxIDUuNTQyLDEuNDA1MyAxLjY0NzIsMC45NDYyIDMuMTI3LDIuMjgxOSA0LjQzOTIsNC4wMjEybDAgLTI0LjA4NDkgMTMuODIwMiAwIDAgNjMuNTg2MiAtMTEuOTkxNSAwIC0wLjk3NzIgLTUuNDEyNGMtMS4zNjgsMi4wMzE0IC0yLjk0NTQsMy41ODk3IC00Ljc2MDIsNC42NjExIC0xLjgxNDgsMS4wNzE0IC0zLjg2NjksMS42MTQgLTYuMTU2MywxLjYxNCAtNS40NTgyLDAgLTkuNjc0MSwtMi4wMTc1IC0xMi42NzU0LC02LjAzODYgLTMuMDAxNCwtNC4wMjExIC00LjUwOSwtOS4zMzYyIC00LjUwOSwtMTUuOTQ1M2wwIC0wLjg0ODh6bTEzLjgzNDEgMC44NDg4YzAsMy41ODk3IDAuNTE2NSw2LjM4NjUgMS41NjM1LDguMzkwMSAxLjA2MSwxLjk4OTYgMi44MTk4LDIuOTkxNCA1LjMwNDYsMi45OTE0IDEuNDM3OSwwIDIuNzIyMiwtMC4yNjQzIDMuODI1LC0wLjc5MyAxLjEwMjksLTAuNTI4OCAyLjAxMDIsLTEuMzA4IDIuNzIyMiwtMi4zNTE1bDAgLTE3LjgwOTdjLTAuNzEyLC0xLjE2ODggLTEuNjE5MywtMi4wNzMxIC0yLjcwODIsLTIuNjk5MyAtMS4wODg5LC0wLjYyNjEgLTIuMzQ1MywtMC45MzIyIC0zLjc1NTIsLTAuOTMyMiAtMi40NTY5LDAgLTQuMjI5NywxLjEyNyAtNS4zMTg2LDMuMzgxIC0xLjA4ODgsMi4yNTQxIC0xLjYzMzMsNS4yNDU1IC0xLjYzMzMsOC45NzQ0bDAgMC44NDg4em04MS42NjQ2IDEzLjI1OTljLTEuNDA5OSwyLjE3MDYgLTMuODgwOCw0LjE3NDIgLTcuMzg0Nyw1Ljk5NjggLTMuNTAzOSwxLjgyMjggLTguMDI2OCwyLjcyNzIgLTEzLjU5NjgsMi43MjcyIC03LjExOTUsMCAtMTIuOTk2NSwtMi4yNjc5IC0xNy42NDUxLC02LjgwMzkgLTQuNjQ4NiwtNC41MzU5IC02Ljk2NiwtMTAuNDA3NSAtNi45NjYsLTE3LjYxNWwwIC0xMi4yMzAyYzAsLTcuMjA3MyAyLjE2MzgsLTEzLjA3OSA2LjQ5MTMsLTE3LjYxNDkgNC4zNDE1LC00LjUzNTkgOS45MjU0LC02LjgwMzkgMTYuNzc5NywtNi44MDM5IDYuODEyNCwwIDEyLjE3MjksMS42Njk3IDE2LjA2NzcsNS4wMDkgMy44OTQ3LDMuMzUzMiA1Ljg5MSw3LjY1MjYgNS45NzQ4LDEyLjkyNmwtMC4wODM4IDAuMjUwNCAtNy42OTE5IDBjLTAuMjUxMywtMy40NTA2IC0xLjU2MzUsLTYuMzAzIC0zLjk1MDYsLTguNTI5MiAtMi4zODcxLC0yLjIyNjEgLTUuODIxMiwtMy4zMzkzIC0xMC4zMTYyLC0zLjMzOTMgLTQuNTY0OSwwIC04LjIyMjMsMS42OTc1IC0xMS4wMTQzLDUuMDkyNSAtMi43NzgsMy4zOTQ5IC00LjE2LDcuNzA4MyAtNC4xNiwxMi45MjZsMCAxMi4zMTM2YzAsNS4yNzM0IDEuNTQ5NSw5LjYxNDUgNC42NjI2LDEzLjAyMzUgMy4wOTkxLDMuNDA4OCA3LjA0OTYsNS4xMjAzIDExLjg1MTgsNS4xMjAzIDMuMzc4MywwIDYuMTU2MywtMC40NDUzIDguMzA2LC0xLjM0OTcgMi4xNDk4LC0wLjg5MDUgMy42OTk0LC0xLjkyMDEgNC42MjA3LC0zLjA2MWwwIC0xMy40OTY1IC0xMy4wNTIzIDAgMCAtNi4zMTY5IDIxLjEwNzEgMCAwIDIxLjc3NTJ6bTQwLjUzOTIgMS4zMzU3Yy0xLjM5NiwyLjM2NTQgLTMuMTgyOCw0LjE4ODEgLTUuMzYwNSw1LjQ2ODIgLTIuMTc3OCwxLjI4MDEgLTQuNzA0NSwxLjkyMDEgLTcuNTY2MiwxLjkyMDEgLTQuODMwMSwwIC04LjU4NTMsLTEuNTMwNiAtMTEuMjkzNSwtNC42MDU1IC0yLjY5NDIsLTMuMDc1IC00LjA0ODMsLTcuODYxMyAtNC4wNDgzLC0xNC4zNTkxbDAgLTI2LjAwNSA4LjA1NDggMCAwIDI2LjA4ODVjMCw0LjcwMjkgMC42OTc5LDcuOTQ0OCAyLjA5MzksOS43MTE4IDEuMzgyMSwxLjc2NzEgMy41NTk4LDIuNjQzNyA2LjUwNTMsMi42NDM3IDIuODYxOCwwIDUuMjIwOSwtMC41ODQ0IDcuMDkxNiwtMS43MzkzIDEuODcwNiwtMS4xNTQ4IDMuMjgwNSwtMi43ODI3IDQuMjI5OCwtNC45MTE1bDAgLTMxLjc5MzIgOC4wNTQ4IDAgMCA0NC4xMDY5IC03LjIzMTIgMCAtMC41MzA1IC02LjUyNTZ6bTI4LjIxMjcgNi41MjU2bC04LjA1NDggMCAwIC00NC4xMDY5IDguMDU0OCAwIDAgNDQuMTA2OXptMCAtNTUuNDA0OWwtOC4wNTQ4IDAgMCAtOC4xOTUzIDguMDU0OCAwIDAgOC4xOTUzem0xMC41NTM2IDM0LjE3MjRjMCwtNy4wOTYxIDEuNTIxNiwtMTIuODI4NSA0LjU2NDksLTE3LjE2OTYgMy4wNDMyLC00LjM1NTEgNy4zMDEsLTYuNTI1NyAxMi43NzMyLC02LjUyNTcgMi41OTY1LDAgNC44ODU5LDAuNDczMSA2Ljg5NjIsMS40MzMxIDIuMDEwMSwwLjk0NjIgMy43MTMyLDIuMzM3NiA1LjEzNzEsNC4xNDY0bDAgLTI0LjIzNzkgOC4wNTQ4IDAgMCA2My41ODYyIC02LjU4OSAwIC0wLjkzNTMgLTUuNDI2NGMtMS40NTE4LDIuMDczMiAtMy4yMjQ3LDMuNjMxNiAtNS4zMTg3LDQuNjg5IC0yLjEwNzksMS4wNzEzIC00LjUzNjksMS42MDAxIC03LjMyODgsMS42MDAxIC01LjM4ODUsMCAtOS42MTg0LC0xLjk0OCAtMTIuNjc1NiwtNS44NTc3IC0zLjA1NzIsLTMuODk1OSAtNC41Nzg4LC05LjAzMDEgLTQuNTc4OCwtMTUuMzg4OGwwIC0wLjg0ODd6bTguMDU0OCAwLjg0ODdjMCw0LjQ2NjQgMC45MjE0LDguMDI4MyAyLjc1MDEsMTAuNjk5OCAxLjgxNDgsMi42ODU0IDQuNjYyNiw0LjAyMTEgOC41MDE1LDQuMDIxMSAyLjQwMTEsMCA0LjQxMTMsLTAuNTQyNyA2LjA1ODUsLTEuNjI3OSAxLjYzMzQsLTEuMDg1MyAyLjk1OTYsLTIuNjE1OCA0LjAwNjUsLTQuNTYzOGwwIC0yMC41OTI0Yy0xLjA0NjksLTEuODIyOCAtMi4zODcxLC0zLjI2OTggLTQuMDM0MywtNC4zNDExIC0xLjY0NzMsLTEuMDcxNCAtMy42Mjk2LC0xLjYxNCAtNS45NDcsLTEuNjE0IC0zLjg4MDcsMCAtNi43Mjg2LDEuNTg2MiAtOC41NzEzLDQuNzcyNCAtMS44NDI2LDMuMTcyMyAtMi43NjQsNy4zMDQ3IC0yLjc2NCwxMi4zOTcybDAgMC44NDg3em01OS43NDc4IDIxLjI0NjVjLTYuMTU2MiwwIC0xMS4wNTYxLC0yLjA0NTQgLTE0LjY5OTYsLTYuMTM2IC0zLjY0MzUsLTQuMDkwNyAtNS40NTgyLC05LjQwNTggLTUuNDU4MiwtMTUuOTE3NWwwIC0xLjc5NDljMCwtNi4yODkgMS44NzA1LC0xMS41MDY3IDUuNjI1NywtMTUuNjgwOSAzLjc0MTIsLTQuMTc0IDguMTgwNSwtNi4yNjEyIDEzLjMwMzcsLTYuMjYxMiA1Ljk3NDgsMCAxMC40ODM4LDEuNzk1IDEzLjU0MSw1LjM4NDcgMy4wNTcyLDMuNTg5OCA0LjU3ODgsOC4zNzYxIDQuNTc4OCwxNC4zNTlsMCA1LjAwOTEgLTI4LjcwMTMgMCAtMC4xMjU2IDAuMjA4NmMwLjA4MzcsNC4yMjk5IDEuMTU4Niw3LjcyMjMgMy4yMjQ3LDEwLjQ0OTQgMi4wOCwyLjc0MSA0Ljk4MzYsNC4xMDQ2IDguNzEwOCw0LjEwNDYgMi43MzYyLDAgNS4xMjMzLC0wLjM4OTYgNy4xODk0LC0xLjE1NDkgMi4wNTIxLC0wLjc3OTIgMy44MjQ5LC0xLjg1MDUgNS4zMzI2LC0zLjIwMDJsMy4xNDEgNS4yMDM4Yy0xLjU3NzUsMS41NTgzIC0zLjY1NzUsMi44NTIzIC02LjI1NCwzLjg4MiAtMi41ODI2LDEuMDI5NiAtNS43MjM1LDEuNTQ0NCAtOS40MDksMS41NDQ0em0tMS4yMjg0IC0zOS40NzM2Yy0yLjY5NDIsMCAtNC45OTc2LDEuMTQwOSAtNi45MTAxLDMuNDA5IC0xLjg5ODUsMi4yNjc5IC0zLjA3MTIsNS4xMjAyIC0zLjUxNzksOC41NDI5bDAuMDgzOCAwLjIwODggMjAuNDA5MiAwIDAgLTEuMDU3NWMwLC0zLjE1ODQgLTAuODM3NSwtNS44MDIgLTIuNTI2NywtNy45MTY5IC0xLjcwMzEsLTIuMTI4OCAtNC4yMDE5LC0zLjE4NjMgLTcuNTM4MywtMy4xODYzeiIvPgogPC9nPgo8L3N2Zz4K\'); background-repeat: no-repeat; background-position: top left; background-size: contain;">&nbsp;</div></a>
                <div style="padding: 20px;">' . str_replace('{[lang_suffix]}', $dic['lang_suffix'], $html) . '</div>
                <div style="line-height: 4px; background-color: #38343f; color: #fff; padding-left: 20px; position: fixed; bottom: 0; left: 0; width: 100%;">
                <p>&nbsp;</p>
                <p><br/>' . $dic['FoodGuide – the most complete</p><p>encyclopedia of cafes and restaurants</p><p>of Ukraine.'] . '.</p>
                <p style="text-align: center;"><strong>' . $dic['© [year]. ALL RIGHTS RESERVED.'] . '</strong></p>
                <p>&nbsp;</p>
                </div>
                </div>',
                    "MIME-Version: 1.0\r\n" .
                    "Content-type: text/html; charset=UTF-8\r\n"
            );
        }
    }

    staticGlobals::init();
?>