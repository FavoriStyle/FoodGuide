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
    })();
?>