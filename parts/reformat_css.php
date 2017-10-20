<?php
    (function(){
        $files = [];
        foreach(scandir($_SERVER['DOCUMENT_ROOT'] . '/wp-content/cache/autoptimize/css/') as $file){
            if(strlen($file) == 48 && preg_match('/^autoptimize_/', $file)){
                $files[] = $file;
            }
        }
        makeDir(WPMU_PLUGIN_DIR . '/openparts/reformat_css');
        file_put_contents(WPMU_PLUGIN_DIR . '/openparts/reformat_css/done.list', '', FILE_APPEND | LOCK_EX);
		$list = json_decode(file_get_contents(WPMU_PLUGIN_DIR . '/openparts/reformat_css/done.list'), true);
        if (!$list) $list = [];
        $old_list = $list;
		foreach ($files as $file){
			if (!in_array($file, $list)){
                file_put_contents(
                    $_SERVER['DOCUMENT_ROOT'] . '/wp-content/cache/autoptimize/css/' . $file,
                    (function($contents){
                        $res = @preg_replace(
                            '(https?:)?\/\/foodguide.in.ua\/.*?\/fonts?\/(\.\.\/\.\.\/fonts?\/)?',
                            'https://cdn.jsdelivr.net/gh/FavoriStyle/FoodGuide@0.0.1-a/assets/fonts/',
                            $contents,
                            true
                        );
                        if ($res) return $res; else return $contents;
                    })(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/wp-content/cache/autoptimize/css/' . $file)),
                    LOCK_EX);
                $list[] = $file;
            }
        }
        if($old_list != $list) file_put_contents(WPMU_PLUGIN_DIR . '/openparts/reformat_css/done.list', json_encode($list), LOCK_EX);
    })();
?>