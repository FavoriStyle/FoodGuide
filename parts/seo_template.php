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
            $callback = function($matches){
                var_dump($matches);
                return $matches[0];
            };
            return preg_replace_callback('/([^\\])\[(categor(ies|y)|name|city|address|N|h1)\]/', $callback, $output);
        });        
    })();
?>