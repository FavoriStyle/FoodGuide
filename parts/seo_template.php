<?php
    (function(){
        $mysql_result = function($sql){
            $sql = preg_replace_callback('/FROM\s+`(.+?)`/ms', function($matches){
                global $wpdb;
                if(!(mb_strpos($matches[1], $wpdb -> prefix) === 0)) $matches[1] = $wpdb -> prefix . $matches[1];
                return 'FROM `' . $matches[1] . '`';
            }, $sql);
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
        };
        ob_start();
        add_action('shutdown', function(){
            $final = '';
            $levels = ob_get_level();
            for ($i = 0; $i < $levels; $i++) {
                $final .= ob_get_clean();
            }
            echo apply_filters('place_restricted_css_to_cdn', apply_filters('final_output_seo', $final));
        }, 0);
        add_filter('final_output_seo', function($output) use ($mysql_result){
            $is = function($class) use ($output){
                if(preg_match('/<html[^>]*class="[^>"]*( |\\b)' . $class . '[^>"]*"[^>]*>/', $output)) return true; else return false;
            };
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
                if ($is_admin_page) return '[' . $do_case('address', $case_mode) . ']';
                if ($ait_post_data) return $ait_post_data['map']['address']; else return '';
            };
            $variables = [
                'category' => function($case_mode /* 0 - first lower; 1 - first upper; 2 - all upper */) use ($do_case, $is_admin_page, $mysql_result){
                    if ($is_admin_page) return '[' . $do_case('category', $case_mode) . ']';
                    global $post;
                    ob_start();
                    $a = $mysql_result('SELECT * FROM `posts_main_categories` WHERE `post_id` = ' . $post -> ID);
                    if ($a && count($a) > 0) $a = $a[0]['category_id']; else {
                        $a = $mysql_result('SELECT `' . get_locale() . '` FROM `custom_translates` WHERE `string` = "default item category id"');
                        if ($a && count($a) > 0) $a = $a[0][get_locale()]; else $a = false;
                        if (!$a || $a == '') $a = 0;
                    }
                    $a = $mysql_result('SELECT `name` FROM `terms` WHERE `term_id` = ' . $a);
                    if ($a && count($a) > 0) return $do_case(iconv(mb_detect_encoding($a[0]['name'], mb_detect_order(), true), "UTF-8", $a[0]['name']), $case_mode); else return '';
                },
                'name' => function($case_mode) use ($do_case, $is_admin_page){
                    if ($is_admin_page) return '[' . $do_case('name', $case_mode) . ']';
                    global $post;
                    return $do_case($post -> post_title, $case_mode);
                },
                'city' => function($case_mode) use ($do_case, $is_admin_page, &$ait_post_data){
                    if ($is_admin_page) return '[' . $do_case('city', $case_mode) . ']';
                    global $post;
                    if ($ait_post_data) return $do_case(explode(',', $ait_post_data['map']['address'])[0], $case_mode); else return '';
                },
                '(save case) => address' => $addr_callback,
                '(save case) => Address' => $addr_callback,
                '(save case) => ADDRESS' => function() use($addr_callback, $do_case){
                    return $do_case($addr_callback(2), 2);
                },
                '(save case) => N' => function() use ($is, $is_admin_page){
                    if ($is_admin_page) return '[N]';
                    $reg_res = [];
                    if ($is('categories-page') && preg_match('/\/page\/(\d+)\//', $_SERVER['REQUEST_URI'], $reg_res)) return $reg_res[1];
                    return '1';
                },
                '(save case) => h1' => function() use ($get_first_header, $is_admin_page){
                    if ($is_admin_page) return '[h1]';
                    return (function($a){if($a)return$a;else return '';})($get_first_header());
                },
            ];
            $vars_table = [];
            $regexp = (function() use ($variables, &$vars_table){
                $res = '/([^\\\])\[(';
                foreach ($variables as $key => $value){
                    $save_case = false;
                    if (strpos($key, '(save case) => ') === 0){
                        $save_case = true;
                        $key = mb_substr($key, 15);
                    }
                    if (!$save_case) $will = '[' . mb_strtoupper(mb_substr($key, 0, 1)) . mb_strtolower(mb_substr($key, 0, 1)) . ']' . mb_strtolower(mb_substr($key, 1)) . '|' . mb_strtoupper($key) . '|'; else $will = $key . '|';
                    $res .= $will;
                    $vars_table['/^' . mb_substr($will, 0, -1) . '$/'] = $value;
                }
                return mb_substr($res, 0, -1) . ')\]/';
            })();
            $callback = function($matches) use (&$vars_table){
                foreach ($vars_table as $key => $value){
                    if(preg_match($key, $matches[2])) return $matches[1] . $value((function($text){if(mb_strtoupper($text)==$text)return 2;elseif(mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text,1)==$text)return 1;else return 0;})($matches[2]));
                }
            };
            return preg_replace_callback($regexp, $callback, $output);
        });
        add_filter('place_restricted_css_to_cdn', function($output){
            $cdn_css = [
                'reset.css',
                'alert.css',
            ];
            foreach($cdn_css as $i => $css){
                $cdn_css[$i] = str_replace('/', '\\/', preg_quote($css));
            }
            $cdn_css = implode('|', $cdn_css);
            return preg_replace_callback('/https?:\/\/[^\/]+\/wp\-content\/themes\/[^\/]+\/design\/css\/(' . $cdn_css . ')/mis', function($matches){
                return 'https://cdn.jsdelivr.net/gh/FavoriStyle/FoodGuide@0.0.1-g/assets/css/' . $matches[1];
            }, $output);
        });
    })();
?>