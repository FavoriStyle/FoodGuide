<?php
    (function(){
        ob_start();
        
        add_action('shutdown', function(){
            $final = '';
        
            // We'll need to get the number of ob levels we're in, so that we can iterate over each, collecting
            // that buffer's output into the final output.
            $levels = ob_get_level();
        
            for ($i = 0; $i < $levels; $i++) {
                $final .= ob_get_clean();
            }
        
            // Apply any filters to the final output
            echo apply_filters('final_output_seo', $final);
        }, 0);
        
        add_filter('final_output_seo', function($output){
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
                '(save case) => N' => function(){
                    //code
                },
                '(save case) => h1' => function(){
                    //code
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