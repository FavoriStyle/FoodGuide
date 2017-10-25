<?php
    (function(){
        add_filter( 'the_content', function($content){
            var_dump($content);
        });
        add_shortcode('test_scode', function($attrs){
            return 'There will be some content';
        });        
    })();
?>