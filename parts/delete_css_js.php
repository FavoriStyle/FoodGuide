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
        add_action('init', function(){
            global $wp_scripts, $wp_styles;
            $scrpts = ((array) $wp_scripts);//['registered'];
            //echo "\n\n" . $scrpts[$name] -> handle . "\n\n" . $scrpts[$name] -> src . "\n\n";
            echo json_encode($scrpts);
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