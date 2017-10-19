<?php
    (function(){
        $files = [];
        foreach(scandir($_SERVER['DOCUMENT_ROOT'] . '/wp-content/cache/autoptimize/css/') as $file){
            if(strlen($file) && preg_match('/^autoptimize_/', $file)){
                $files[] = $file;
            }
        }
        var_dump($files);
    })();
?>