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
                if(preg_match('/<html[^>]*class="[^>"]*( |\\b)' + $class + '[^>"]*"[^>]*>/', $output)) return true; else return false;
            };
            $get_first_tag_inner_html = function($tag) use ($output){
                $DOM = new DOMDocument;
                if ($DOM -> loadHTML($output)){
                    return ($dom -> getElementsByTagName($tag))[0] -> nodeValue;
                }
                return '';
            };
            $variables = [
                'category' => function(){
                    //code
                },
                'categories' => function(){
                    //code
                },
                'name' => function(){
                    //code
                },
                'city' => function(){
                    //code
                },
                'address' => function(){
                    //code
                },
                '(save case) => N' => function() use ($is){
                    $reg_res = [];
                    if ($is('categories-page') && preg_match('/\/page\/(\d+)\//', $_SERVER['REQUEST_URI'], $reg_res)) return $reg_res[1];
                    return '';
                },
                '(save case) => h1' => function() use ($get_first_tag_inner_html){
                    return $get_first_tag_inner_html('h1');
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
            $callback = function($matches, &$vars_table){
                var_dump($matches);
                return $matches[0];
            };
            return preg_replace_callback($regexp, $callback, $output);
        });        
    })();
?>