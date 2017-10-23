<?php
    (function(){
        add_action('wp_print_scripts', function(){
            global $wp_scripts, $wp_styles;
            var_dump($wp_scripts);
            echo 'SCRIPTS: ';
            foreach( $wp_scripts->queue as $handle ) :
                echo $handle . ' | ';
            endforeach;
            echo 'STYLES: ';
            foreach( $wp_styles->queue as $handle ) :
                echo $handle . ' | ';
            endforeach;
        });
        add_action('init', function(){
            foreach ([
                // scripts and styles to remove
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