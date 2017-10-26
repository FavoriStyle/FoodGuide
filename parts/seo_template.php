<?php
    (function(){
        ob_start();
        add_action('shutdown', function(){
            $final = '';
            $levels = ob_get_level();
            for ($i = 0; $i < $levels; $i++) {
                $final .= ob_get_clean();
            }
            echo apply_filters('final_output_seo', $final);
        }, 0);
        add_filter('final_output_seo', function($output){
            $is = function($class) use ($output){
                if(preg_match('/<html[^>]*class="[^>"]*( |\\b)' . $class . '[^>"]*"[^>]*>/', $output)) return true; else return false;
            };
            $get_first_header = function() use ($output){
                $reg_res = [];
                if(preg_match('/<h1[^>]*>(.+?)<\\/h1>/mis', $output, $reg_res)) return $reg_res[1]; else return false;
            };
            $do_case = function($res, $case_mode){
                if ($case_mode == 2) return mb_strtoupper($res); elseif ($case_mode == 1) return mb_strtoupper($res[0]) . mb_strtolower(mb_substr($res, 1)); else return mb_strtolower($res);
            };
            $variables = [
                'category' => function($case_mode /* 0 - first lower; 1 - first upper; 2 - all upper */){
                    //global $post;
                    //ob_start();
                    //var_dump($post -> post_title);
                    //return ob_get_clean();
                    //code
                },
                'categories' => function($case_mode){
                    //code
                },
                'name' => function($case_mode) use ($do_case){
                    global $post;
                    return $do_case($post -> post_title, $case_mode);
                    //code
                },
                'city' => function($case_mode){
                    //code
                },
                'address' => function($case_mode){
                    //code
                },
                '(save case) => N' => function() use ($is){
                    $reg_res = [];
                    if ($is('categories-page') && preg_match('/\/page\/(\d+)\//', $_SERVER['REQUEST_URI'], $reg_res)) return $reg_res[1];
                    return '1';
                },
                '(save case) => h1' => function() use ($get_first_header){
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
                    if (!$save_case) $will = '[' . mb_strtoupper($key[0]) . mb_strtolower($key[0]) . ']' . mb_strtolower(mb_substr($key, 1)) . '|' . mb_strtoupper($key) . '|'; else $will = $key . '|';
                    $res .= $will;
                    $vars_table['/^' . mb_substr($will, 0, -1) . '$/'] = $value;
                }
                return mb_substr($res, 0, -1) . ')\]/';
            })();
            $callback = function($matches) use (&$vars_table){
                foreach ($vars_table as $key => $value){
                    if(preg_match($key, $matches[2])) return $matches[1] . $value((function($text){if(mb_strtoupper($text)==$text)return 2;elseif(mb_strtoupper($text[0]).mb_substr($text,1)==$text)return 1;else return 0;})($matches[2]));
                }
            };
            return preg_replace_callback($regexp, $callback, $output);
        });        
    })();
?>