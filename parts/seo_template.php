<?php
    (function(){
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
        add_action('shutdown', function(){
            $final = '';
            $levels = ob_get_level();
            for ($i = 0; $i < $levels; $i++){
                $final .= ob_get_clean();
            }
            echo apply_filters('place_restricted_css_to_cdn', apply_filters('final_output_seo', apply_filters('final_output_seo', $final)));
        }, 0);
        add_filter('final_output_seo', function($output) use ($mysql_result){
            $is = function($class) use ($output){
                if(preg_match('/<html[^>]*class="[^>"]*( |\\b)' . $class . '[^>"]*"[^>]*>/', $output)) return true; else return false;
            };
            $is_paginating = false;
            $page_num = (function() use (&$is_paginating){
                $reg_res = [];
                $is_paginating = !!preg_match('/\/page\/(\d+)\//', $_SERVER['REQUEST_URI'], $reg_res);
                if ($is_paginating) return $reg_res[1]; else return '0';
            })();
            $is_admin_page = !!preg_match('/\\/wp\\-admin\\//', $_SERVER['REQUEST_URI']);
            $get_first_header = function() use ($output){
                $reg_res = [];
                if(preg_match('/<h1[^>]*>(.+?)<\\/h1>/mis', $output, $reg_res)) return $reg_res[1]; else return false;
            };
            $do_case = function($res, $case_mode){
                if ($case_mode == 2) return mb_strtoupper($res); elseif ($case_mode == 1) return mb_strtoupper(mb_substr($res, 0, 1)) . mb_strtolower(mb_substr($res, 1)); else return mb_strtolower($res);
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
            $addr_callback = function($case_mode) use ($do_case, $is_admin_page, &$ait_post_data){
                if ($is_admin_page) return '[{' . $do_case('address', $case_mode) . '}]';
                if ($ait_post_data) return $ait_post_data['map']['address']; else return '';
            };
            $get_cat = function($case_mode) use($do_case, $mysql_result){
                global $post;
                $a = $mysql_result('SELECT * FROM `posts_main_categories` WHERE `post_id` = ' . $post -> ID);
                if ($a && count($a) > 0) $a = $a[0]['category_id']; else {
                    $a = $mysql_result('SELECT `' . get_locale() . '` FROM `custom_translates` WHERE `string` = "default item category id"');
                    if ($a && count($a) > 0) $a = $a[0][get_locale()]; else $a = false;
                    if (!$a || $a == '') $a = 0;
                }
                $a = $mysql_result('SELECT `name` FROM `terms` WHERE `term_id` = ' . $a);
                if ($a && count($a) > 0) return $do_case(iconv(mb_detect_encoding($a[0]['name'], mb_detect_order(), true), "UTF-8", $a[0]['name']), $case_mode); else return '';
            };
            $variables = [
                'category' => function($case_mode /* 0 - first lower; 1 - first upper; 2 - all upper */) use ($is_admin_page, $get_cat){
                    if ($is_admin_page) return '[{' . $do_case('category', $case_mode) . '}]';
                    return $get_cat($case_mode);
                },
                'single_category' => function($case_mode) use ($is_admin_page, $get_cat, $mysql_result){
                    if ($is_admin_page) return '[{' . $do_case('single_category', $case_mode) . '}]';
                    $cat = $get_cat($case_mode);
                    $a = $mysql_result('SELECT `single` FROM `categories_singles` WHERE `category` = FROM_BASE64(\'' . base64_encode($cat) . '\')', (function(){
                        return new class {
                            public function log($a){
                                var_dump($a);
                            }
                            public function warn($a){}
                            public function error($a){}
                        };
                    })());
                    if ($a) return iconv(mb_detect_encoding($a[0]['single'], mb_detect_order(), true), "UTF-8", $a[0]['single']); else return $cat;
                },
                'name' => function($case_mode) use ($do_case, $is_admin_page){
                    if ($is_admin_page) return '[{' . $do_case('name', $case_mode) . '}]';
                    global $post;
                    return $do_case($post -> post_title, $case_mode);
                },
                'city' => function($case_mode) use ($do_case, $is_admin_page, &$ait_post_data){
                    if ($is_admin_page) return '[{' . $do_case('city', $case_mode) . '}]';
                    global $post;
                    if ($ait_post_data) return $do_case(explode(',', $ait_post_data['map']['address'])[0], $case_mode); else return '';
                },
                'page_x' => function($case_mode) use ($do_case, $is_admin_page){
                    if ($is_admin_page) return '[{' . $do_case('page_x', $case_mode) . '}]';
                    return $do_case(mb_substr(__('Page %s', 'ait'), 0, -3), $case_mode);
                },
                '(save case) => address' => function() use($addr_callback, $do_case){
                    return $do_case($addr_callback(0), 0);
                },
                '(save case) => Address' => function() use($addr_callback, $do_case){
                    return $do_case($addr_callback(1), 1);
                },
                '(save case) => ADDRESS' => function() use($addr_callback, $do_case){
                    return $do_case($addr_callback(2), 2);
                },
                '(save case) => N' => function() use ($page_num, $is_admin_page){
                    if ($is_admin_page) return '[{N}]';
                    return $page_num;
                },
                '(save case) => h1' => function() use ($get_first_header, $is_admin_page, $do_case){
                    if ($is_admin_page) return '[{h1}]';
                    return (function($a){if($a)return$a;else return '';})($do_case(trim(strip_tags($get_first_header())), 1));
                },
                '(regexp) => setDefault\\(([^\\)]+?)\\);\\s*setNumbered\\(([^\\)]+?)\\)' => function($matches) use ($page_num, $is_admin_page){
                    if ($is_admin_page) return '[{' . $matches[0] . '}]';
                    return ($page_num == 0 ? $matches[1] : $matches[2]);
                },
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
            return preg_replace_callback($regexp, $callback, $output);
        });
        add_filter('place_restricted_css_to_cdn', function($output){
            //if (!preg_match('/' . preg_quote('https://' . $_SERVER['HTTP_HOST'] . '/wp-content/uploads/cache/fgc/style-0.0.1.css') . '/', $output)) $output = str_replace('</body>', '<link href="https://' + $_SERVER['HTTP_HOST'] + '/wp-content/uploads/cache/fgc/style-0.0.1.css" rel="stylesheet"></body>');
            return $output;
        });
    })();
?>