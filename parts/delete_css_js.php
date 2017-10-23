<?php
    (function(){
        add_action('wp_print_scripts', function(){
            global $wp_scripts, $wp_styles;
            echo 'SCRIPTS: ';
            foreach( $wp_scripts->queue as $handle ) :
                echo $handle . ' | ';
            endforeach;
            echo 'STYLES: ';
            foreach( $wp_styles->queue as $handle ) :
                echo $handle . ' | ';
            endforeach;
        });
        add_action('wp_print_scripts', function(){
            global $wp_scripts, $wp_styles;
            echo "\n\n" . json_encode($wp_scripts) . "\n\n" . json_encode($wp_styles) . "\n\n";
            foreach ([
                // scripts and styles to remove (MUST BE EXCLUDED FROM AUTOPTIMIZE LIST)
                'scripts' => [
                    'dragscroll',
                    'jquery-optiscroll'
                ],
                'styles' => [
                    'optiscroll'
                ]
            ] as $what => $name){
                if ($what = 'scripts'){
                    wp_deregister_script($name);
                    wp_dequeue_script($name);
                } else {
                    wp_deregister_style($name);
                    wp_dequeue_style($name);
                }
            }        
        });        
    })();
?>