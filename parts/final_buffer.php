<?php
    (function(){
        $html = new class{
            private $attrs = [];
            public function attr($name, $value = null){
                if ($value != null) $this -> attrs[$name] = $value; else return $this -> attrs[$name];
            }
            public function addClass($name){
                $this -> attrs['class'] .= ' ' . $name;
            }
            public function hasClass($name){
                try{
                    foreach(explode(' ', $this -> attrs['class']) as $class){
                        if($class == $name) return true;
                    }
                } catch(Exception $e){}
                return false;
            }
            public function removeClass($name){
                $list = explode(' ', $this -> attrs['class']);
                foreach($list as $i => $class){
                    if($class == $name) unset($list[$i]);
                }
                $this -> attrs['class'] = implode(' ', $list);
            }
            public function generate(){
                $str = '<html';
                foreach($this -> attrs as $attr => $val){
                    if ($attr != 'class' || $val != ''){
                        if ($val == '') $str .= " $attr"; else $str .= " $attr=\"$val\"";
                    }
                }
                return $str . '>';
            }
            public function parseTag($str){
                preg_replace_callback('/<html([^>]*)>/', function($matches){
                    $matches[1] = preg_replace_callback('/"([^"]*)"/', function($matches2){
                        return '"' . base64_encode($matches2[1]) . '"';
                    }, $matches[1]);
                    $matches[1] = preg_split('/\s+/', $matches[1]);
                    array_shift($matches[1]);
                    $this -> attrs = ['class'=>''];
                    foreach($matches[1] as $i => $attr){
                        $attr = explode('="', $attr, 2);
                        $attr[1] = ($attr[1] ? base64_decode(substr($attr[1], 0, -1)) : null);
                        $this -> attrs[$attr[0]] = $attr[1];
                    }
                }, $str);
            }
        };
        $mysql_result = (function($func){
            return function($sql, $debugConsole = false) use ($func){
                if (!$debugConsole){
                    $noop = function(){};
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
        ob_start();
        add_action('shutdown', function() use (&$html){
            $final = '';
            $levels = ob_get_level();
            for ($i = 0; $i < $levels; $i++){
                $final .= ob_get_clean();
            }
            (function/* init $html */() use ($final, &$html){
                preg_replace_callback('/<html[^>]*>/', function($matches) use (&$html){
                    $html -> parseTag($matches[0]);
                }, $final);
            })();
            echo apply_filters('sitemap_xml', apply_filters('logo_text', apply_filters('categories_bar', apply_filters('final_output_seo', apply_filters('final_output_seo', apply_filters('prepare_regexp_search', $final))))));
        }, 0);
        add_filter('prepare_regexp_search', function($output){
            return preg_replace('/(<p[^>]*>)\\[\\{setDefault\\(([^)<]*<\\/p>)/', '$1$2', $output);
        });
        add_filter('final_output_seo', function($output) use ($mysql_result, &$html){
            $is = function($class) use (&$output){// <---- ВАЖНО использовать указатель
                return !!preg_match('/<(html|body)[^>]*class="[^>"]*( |\\b)' . $class . '[^>"]*"[^>]*>/', $output);
            };
            if($is('ait-easy-admin-enabled')){
                if (!$html -> hasClass('has-locale-changer')){
                    $html -> addClass('has-locale-changer');
                    $uid = get_current_user_id();
                    $profileuser = get_user_to_edit($uid);
                    $languages = get_available_languages();
                    if(isset($_GET['lng'])){
                        if (in_array($_GET['lng'], $languages)){
                            update_user_meta($uid, 'locale', $_GET['lng']);
                        } else {
                            update_user_meta($uid, 'locale', 'en_US');
                        }
                        $deleteGET = function($url, $name, $amp = false) {
                          $url = str_replace("&amp;", "&", $url); // Заменяем сущности на амперсанд, если требуется
                          list($url_part, $qs_part) = array_pad(explode("?", $url), 2, ""); // Разбиваем URL на 2 части: до знака ? и после
                          parse_str($qs_part, $qs_vars); // Разбиваем строку с запросом на массив с параметрами и их значениями
                          unset($qs_vars[$name]); // Удаляем необходимый параметр
                          if (count($qs_vars) > 0) { // Если есть параметры
                            $url = $url_part."?".http_build_query($qs_vars); // Собираем URL обратно
                            if ($amp) $url = str_replace("&", "&amp;", $url); // Заменяем амперсанды обратно на сущности, если требуется
                          }
                          else $url = $url_part; // Если параметров не осталось, то просто берём всё, что идёт до знака ?
                          return $url; // Возвращаем итоговый URL
                        };
                        header('Location: ' . $deleteGET($_SERVER['REQUEST_URI'], 'lng'));
                        die();
                    }
                    if ($languages){
                        $output = preg_replace('/\\<ul[^>]*id="easyadmin\\-user\\-menu"[^>]*\\>/', '$0' . preg_replace('/\<select[^>]*/', '$0 style="position:absolute;top:0;z-index:1;right:100%;" onchange="var d=((document.location.href.indexOf(\'?\')+1)?\'&\':\'?\');document.location.href=document.location.href+d+\'lng=\'+this.options[this.selectedIndex].value"', wp_dropdown_languages([
                            'name'                        => 'locale',
                            'id'                          => 'locale-top',
                            'selected'                    => str_replace('-', '_', $html -> attr('lang')),
                            'languages'                   => $languages,
                            'show_available_translations' => false,
                            'show_option_site_default'    => false,
                            'echo'                        => false,
                        ])), $output);
                    }
                }
            }
            $is_paginating = false;
            $page_num = (function() use (&$is_paginating){
                $reg_res = [];
                $is_paginating = !!preg_match('/\/page\/(\d+)\//', $_SERVER['REQUEST_URI'], $reg_res);
                if ($is_paginating) return $reg_res[1]; else return '1';
            })();
            $is_admin_page = !!preg_match('/\\/wp\\-admin\\//', $_SERVER['REQUEST_URI']);
            $get_first_header = function() use ($output){
                $reg_res = [];
                if(preg_match('/<h1[^>]*>(.+?)<\\/h1>/mis', $output, $reg_res)) return $reg_res[1]; else return false;
            };
            $post_meta = (function(){
                global $post;
                if(!$post) return false;
                return get_post_meta($post -> ID);
            })();
            $ait_post_data = (function() use ($post_meta){
                if(!$post_meta) return false;
                $res = @unserialize($post_meta['_ait-item_item-data'][0]);
                if(!$res) $res = $post_meta['_ait-item_item-data'];
                return $res;
            })();
            $addr_callback = function($case_mode) use ($is_admin_page, &$ait_post_data){
                if ($is_admin_page) return '[{' . staticGlobals::do_case('address', $case_mode) . '}]';
                if ($ait_post_data) return $ait_post_data['map']['address']; else return '';
            };
            $get_cat = function($case_mode) use($mysql_result){
                global $post;
                $a = $mysql_result('SELECT * FROM `posts_main_categories` WHERE `post_id` = ' . $post -> ID);
                if ($a && count($a) > 0) $a = $a[0]['category_id']; else {
                    $a = $mysql_result('SELECT `' . get_locale() . '` FROM `custom_translates` WHERE `string` = "default item category id"');
                    if ($a && count($a) > 0) $a = $a[0][get_locale()]; else $a = false;
                    if (!$a || $a == '') $a = 0;
                }
                $a = $mysql_result('SELECT `name` FROM `terms` WHERE `term_id` = ' . $a);
                if ($a && count($a) > 0) return staticGlobals::do_case(staticGlobals::utf8($a[0]['name']), $case_mode); else return '';
            };
            $get_place_city = function($place) use (&$html){
                $google_api_key = Secrets::$google_places_api_key;
                $yandex_api_key = Secrets::$yandex_translate_api_key;
                $google_part = function() use ($place, $google_api_key, &$html){
                    $latest_res = file_get_contents("https://maps.googleapis.com/maps/api/place/textsearch/json?key=$google_api_key&query=" . urlencode($place));
                    if($latest_res){
                        $latest_res = json_decode($latest_res, true);
                        if ($latest_res && $latest_res['status'] && $latest_res['status'] == 'OK' && $latest_res['results'] && $latest_res['results'][0] && $latest_res['results'][0]['place_id']){
                            $latest_res = file_get_contents("https://maps.googleapis.com/maps/api/place/details/json?key=$google_api_key&language=" . $html -> attr('lang') . '&placeid=' . urlencode($latest_res['results'][0]['place_id']));
                            if($latest_res){
                                $latest_res = json_decode($latest_res, true);
                                if ($latest_res && $latest_res['status'] && $latest_res['status'] == 'OK' && $latest_res['result']){
                                    foreach($latest_res['result']['address_components'] as $addr_component){
                                        if(in_array('locality', $addr_component['types'])){
                                            return $addr_component['long_name'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    return false;
                };
                $yandex_part = function($str) use (&$html, $yandex_api_key){
                    $str_orig = $str;
                    if ($html -> attr('lang') == 'ru-RU'){
                        $str = file_get_contents("https://translate.yandex.net/api/v1.5/tr.json/translate?key=$yandex_api_key&text=" . urlencode($str) . '&lang=uk-ru');
                        if($str){
                            $str = json_decode($str, true);
                            if($str && $str['code'] == 200 && $str['text'] && $str['text'][0]){
                                return $str['text'][0];
                            }
                        }
                    }
                    return $str_orig;
                };
                $addr = false;
                //google
                $prefix = '{[' . $html -> attr('lang') . '] google} ';
                if (!OpenpartsCache::cache($prefix . $place)){
                    $addr = $google_part();
                    if(!$addr) $addr = explode(',', $place)[0];
                    OpenpartsCache::cache($prefix . $place, $addr);
                } else $addr = OpenpartsCache::cache($prefix . $place);
                //yandex
                $prefix = '{[' . $html -> attr('lang') . '] yandex} ';
                if (!OpenpartsCache::cache($prefix . $addr)){
                    OpenpartsCache::cache($prefix . $addr, $yandex_part($addr));
                }
                return OpenpartsCache::cache($prefix . $addr);
            };
            $variables = [


                // Начало тех самых шаблонов


                'category' => function($case_mode /* 0 - first lower; 1 - first upper; 2 - all upper */) use ($is_admin_page, $get_cat){
                    if ($is_admin_page) return '[{' . staticGlobals::do_case('category', $case_mode) . '}]';
                    return $get_cat($case_mode);
                },



                'single_category' => function($case_mode) use ($is_admin_page, $get_cat, $mysql_result){
                    if ($is_admin_page) return '[{' . staticGlobals::do_case('single_category', $case_mode) . '}]';
                    return eaDB::categoryToSingle($get_cat($case_mode), $case_mode);
                },



                'name' => function($case_mode) use ($is_admin_page){
                    if ($is_admin_page) return '[{' . staticGlobals::do_case('name', $case_mode) . '}]';
                    global $post;
                    return /*staticGlobals::do_case(*/$post -> post_title/*, $case_mode)*/;
                },



                'city' => function($case_mode) use ($is_admin_page, &$ait_post_data, $get_place_city){
                    if ($is_admin_page) return '[{' . staticGlobals::do_case('city', $case_mode) . '}]';
                    if ($ait_post_data) return staticGlobals::do_case($get_place_city($ait_post_data['map']['address']), $case_mode); else return '';
                },



                'page_x' => function($case_mode) use ($is_admin_page){
                    if ($is_admin_page) return '[{' . staticGlobals::do_case('page_x', $case_mode) . '}]';
                    return staticGlobals::do_case(mb_substr(__('Page %s', 'ait'), 0, -3), $case_mode);
                },



                'categories_list' => function($case_mode) use ($is_admin_page, &$html){
                    if ($is_admin_page) return '[{' . staticGlobals::do_case('categories_list', $case_mode) . '}]';
                    $res = '<ul data-action="up-me delete-container">';
                    $categories = [];
                    $unparse = function($catname, $props, $self) use (&$categories){
                        $categories[$catname] = $props['id'];
                        foreach($props['childs'] as $c => $p){
                            $self($c, $p, $self);
                        }
                    };
                    $cur_lang_index = [
                        'uk'    => 2,
                        'ru-RU' => 1,
                        'en-US' => 0
                    ][$html -> attr('lang')];
                    foreach(json_decode(eaDB::getCategories($cur_lang_index, $cur_lang_index), true) as $catname => $props){
                        $unparse($catname, $props, $unparse);
                    }
                    $categories_rev = [];
                    $cats_base64_normal_case = [];
                    $cats_base64_normal_case_rev = [];
                    foreach($categories as $cat_name => $id){
                        $categories_rev[$id] = $cat_name;
                        $cats_base64_normal_case[$cat_name] = base64_encode(staticGlobals::do_case($cat_name, 1));
                        $cats_base64_normal_case_rev[$cats_base64_normal_case[$cat_name]] = $cat_name;
                    }
                    foreach(staticGlobals::mysql_result('SELECT term_id AS id, slug FROM `terms` WHERE term_id IN (' . implode(', ', $categories) . ') ORDER BY name ASC') as $category){
                        $categories[$categories_rev[$category['id']]] = $category['slug'];
                    }
                    foreach(staticGlobals::mysql_result('SELECT * FROM `categories_singles` WHERE category IN (FROM_BASE64("' . implode('"), FROM_BASE64("', $cats_base64_normal_case) . '")) ORDER BY single ASC') as $names){
                        $names['category'] = staticGlobals::utf8($names['category']);
                        $names['single'] = staticGlobals::utf8($names['single']);
                        $original_cat = $cats_base64_normal_case_rev[base64_encode($names['category'])];
                        if ($categories[$original_cat]){
                            $tmp = $categories[$original_cat];
                            unset($categories[$original_cat]);
                            $categories[staticGlobals::do_case($names['single'], 2)] = $tmp;
                        }
                    }
                    foreach($categories as $cat_name => $slug){
                        $res .= "<li><a href=\"/cat/$slug/\">$cat_name " . eaDB::translate('IN THE CITY', str_replace('-', '_', $html -> attr('lang'))) . "</a></li>";
                    }
                    return $res . '</ul>';
                },



                '(save case) => address' => function() use($addr_callback){
                    return staticGlobals::do_case($addr_callback(0), 0);
                },



                '(save case) => Address' => function() use($addr_callback){
                    return $addr_callback(1);
                },



                '(save case) => ADDRESS' => function() use($addr_callback){
                    return staticGlobals::do_case($addr_callback(2), 2);
                },



                '(save case) => N' => function() use ($page_num, $is_admin_page){
                    if ($is_admin_page) return '[{N}]';
                    return $page_num;
                },


                
                '(save case) => h1' => function() use ($get_first_header, $is_admin_page){
                    if ($is_admin_page) return '[{h1}]';
                    return (function($a){if($a)return$a;else return '';})(staticGlobals::do_case(trim(strip_tags($get_first_header())), 1));
                },


                
                '(regexp) => setDefault\\(([^\\)]+?)\\);\\s*setNumbered\\(([^\\)]+?)\\)' => function($matches) use ($page_num, $is_admin_page){
                    if ($is_admin_page) return '[{' . $matches[0] . '}]';
                    return ($page_num == 1 ? $matches[1] : $matches[2]);
                },



                '(regexp) => grabCuisines\\(([^\\)]+?)\\)' => function($matches) use ($is_admin_page){
                    if ($is_admin_page) return '[{' . $matches[0] . '}]';
                    $res = '<ul>';
                    $target = 'https://' . $_SERVER['SERVER_NAME'] . '/' . $matches[3];
                    $tmp = file_get_contents($target);
                    if ($tmp){
                        $tmp2 = [];
                        preg_match_all('/<option[^>]+value="(#elm\\-toggles\\-[^"]+)"[^>]*>([^<]*?)<\\/option>/', $tmp, $tmp2);
                        for($i = 0; $i < count($tmp2[1]); $i++){
                            $res .= '<li><a href="' . $target . $tmp2[1][$i] . '">' . $tmp2[2][$i] . '</a></li>';
                        }
                    }
                    return $res . '</ul>';
                },



                // Конец самих шаблонов

                
            ];
            $vars_table = [];
            $perform_regexp_from_str = function($str){
                return str_replace('/', '\\/', preg_quote($str));
            };
            $regexp = (function() use ($variables, &$vars_table, $perform_regexp_from_str){
                $res = '/([^\\\])\\[\\{(';
                foreach ($variables as $key => $value){
                    $save_case = false;
                    $regexp = false;
                    if (strpos($key, '(save case) => ') === 0){
                        $save_case = true;
                        $key = mb_substr($key, 15);
                    } elseif (strpos($key, '(regexp) => ') === 0){
                        $regexp = true;
                        $key = mb_substr($key, 12);
                    }
                    if (!$save_case && !$regexp) $will = '[' . $perform_regexp_from_str(mb_strtoupper(mb_substr($key, 0, 1)) . mb_strtolower(mb_substr($key, 0, 1))) . ']' . $perform_regexp_from_str(mb_strtolower(mb_substr($key, 1))) . '|' . $perform_regexp_from_str(mb_strtoupper($key)) . '|'; elseif (!$regexp) $will = $perform_regexp_from_str($key) . '|'; else $will = $key . '|';
                    $res .= $will;
                    $vars_table[($save_case ? '(save case) => ' : '') . ($regexp ? '(regexp) => ' : '') . '/^' . mb_substr($will, 0, -1) . '$/'] = $value;
                }
                return mb_substr($res, 0, -1) . ')\\}\\]/';
            })();
            $callback = function($matches) use (&$vars_table){
                foreach ($vars_table as $key => $value){
                    $save_case = false;
                    $regexp = false;
                    if (strpos($key, '(save case) => ') === 0){
                        $save_case = true;
                        $key = mb_substr($key, 15);
                    } elseif (strpos($key, '(regexp) => ') === 0){
                        $regexp = true;
                        $key = mb_substr($key, 12);
                    }
                    if (!$save_case && !$regexp && preg_match($key, $matches[2])) return $matches[1] . $value((function($text){
                        if (mb_strtoupper($text) == $text) return 2; elseif (mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1) == $text) return 1; else return 0;
                    })($matches[2])); elseif(!$regexp && preg_match($key, $matches[2])) return $matches[1] . $value(); elseif(preg_match($key, $matches[2])) return $matches[1] . $value((function($a){
                        array_shift($a);
                        array_shift($a);
                        return $a;
                    })($matches));
                }
            };
            (function/* update html */() use (&$output, &$html){
                $output = preg_replace_callback('/<html[^>]*>/', function($matches) use (&$html){
                    return $html -> generate();
                }, $output);
            })();
            return preg_replace_callback($regexp, $callback, $output);
        });
        add_filter('categories_bar', function($output){
            return preg_replace_callback('/<div[^>]+class="categories\\-bar"[^>]*>[\\s\\S]*<div class="item\\-categories">([\\s\\S]*)<div[^>]+class="entry\\-content\\-wrap"[^>]*>/', function($matches){
                return preg_replace_callback('/<span>([\\s\\S]*?)<\\/span>/', function($matches){
                    return '<span>' . eaDB::categoryToSingle($matches[1], 2) . '</span>';
                }, $matches[0]);
            }, $output);
        });
        add_filter('sitemap_xml', function($output){
            if (preg_match('/sitemap(-.*)?\\.xml/', $_SERVER['REQUEST_URI'])) return preg_replace_callback('/^([\\s\\S]*?)[\\s\\r\\n]*$/', function($matches){
                return $matches[1];
            }, $output); else return $output;
        });
        add_filter('logo_text', function($output) use (&$html){
            if ($html -> attr('lang')){
                return preg_replace_callback('/(<div[^>]+class="tools\\-bar"[^>]*>[\\s\\S]*)(<div[^>]+class="tools\\-bar\\-right"[^>]*>)/', function($matches) use (&$html){
                    return (function($str){
                        return preg_replace_callback('/([\\s\\S]*)<\\/div>/', function($matches){
                            return $matches[1];
                        }, $str);
                    })($matches[1]) . '</div><div style="height:0;"><h1 class="logo-text">' . eaDB::translate('Logo text', str_replace('-', '_', $html -> attr('lang'))) . '</h1></div>' . $matches[2];
                }, $output);
            } else return $output;
        });
    })();
?>