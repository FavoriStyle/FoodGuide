<?php
    (function(){
        $files = scandir($_SERVER['DOCUMENT_ROOT'] . '/wp-content/cache/autoptimize/css/');
        var_dump($files);
    })();
?>