<?php

/*
Plugin Name: FGC OpenParts static loader
Description: Adds avaiability to load external JS code from jsdelivr hosted on GitHub
Plugin URI: https://github.com/FavoriStyle/FoodGuide/
Version: 0.0.1-a
Author: KaMeHb-UA
Author URI: https://github.com/KaMeHb-UA
License: MIT
*/

(function(){

    define('JS_LOADER_CHANNEL', 'beta'); // beta or stable
    define('CSS_LOADER_CHANNEL', 'beta'); // beta or stable

    $settings = [
		'user' => 'FavoriStyle',
		'repo' => 'FoodGuide'
    ];
    $parts = [
        //parts to be loaded
        [
            'src' => 'main',
            'deps' => [ // js objects that must be loaded before
                'jQuery'
            ]
        ],
    ];
    $css_list = [
        'style'
    ];
    $css_debug_list = [
        //
    ];
    $debug_parts = [
        //parts to be loaded only with --debug or --devel key
    ];

    if(_USER_DEBUG_MODE){
        foreach ($debug_parts as $part){
            $parts[] = $part;
        }
        foreach ($css_debug_list as $part){
            $css_list[] = $part;
        }
    }
    $full_url = function($name, $type, $beta = false) use ($settings){
        if ($beta) return "https://raw.githubusercontent.com/$settings[user]/$settings[repo]/master/assets/$type/$name.$type";
        else return "https://cdn.jsdelivr.net/gh/$settings[user]/$settings[repo]@" . staticGlobals::getCurrentGitHubRelease() . "/assets/$type/$name.min.$type";
    };
    foreach($parts as $i => $part){
        $part['src'] = $full_url($part['src'], 'js', _USER_DEBUG_MODE || JS_LOADER_CHANNEL == 'beta');
        $parts[$i] = $part;
    }
    foreach($css_list as $i => $part){
        $part['src'] = $full_url($part['src'], 'css', _USER_DEBUG_MODE || JS_LOADER_CHANNEL == 'beta');
        $css_list[$i] = $part;
    }
    add_action('wp_enqueue_scripts', function() use ($parts, $css_list){
        ?>
        <script id="static_loder_app">
            window['__app'] = {};
            __app.waitForBody = function(func, args = []){
                setTimeout(function(){
                    if(document.body) func.apply(document, args); else __app.waitForBody(func, args);
                }, 100);
            };
            __app.appendToBody = function(DOMElement){
                try{
                    document.body.appendChild(DOMElement);
                } catch(e) {
                    __app.waitForBody(function(){
                        document.body.appendChild(DOMElement);
                    })
                }
            };
            __app.extend = function(oldObj, newObj){
                for(var i in newObj){
                    oldObj[i] = newObj[i];
                }
                return oldObj;
            };
            __app.createElement = function(settings){
                settings = __app.extend({
                    name : 'div',
                    html : '',
                    attribs : {}
                }, settings);
                var el = document.createElement(settings.name);
                el.innerHTML = settings.html;
                for (var i in settings.attribs){
                    el.setAttribute(i, settings.attribs[i]);
                }
                return el;
            };
        </script>
        <script id="static_loader_css">
            // CSS-файлы будут загружаться параллельно, но рендерится на страничке в СТРОГО ОПРЕДЕЛЁННОМ порядке ради обратной совместимости
            var list = [], expectedCount = 0, doneCount = 0;
            <?php echo json_encode($css_list); ?>.forEach((file, index) => {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', file, true);
                xhr.send();
                xhr.onreadystatechange = () => {
                    if (xhr.readyState != 4) return;
                    if (xhr.status == 200){
                        list[index] = xhr.responseText;
                    } else {
                        list[index] != '';
                    }
                }
                expectedCount++;
            });
            setTimeout(function b(){
                let a = false;
                while(doneCount < expectedCount){
                    if (list[doneCount]){
                        __app.appendToBody(__app.createElement({
                            name: 'style',
                            html : list[doneCount],
                            attribs: {
                                '__data-needed-index' : `${doneCount - 1}`,
                            }
                        }));
                        doneCount++;
                    } else {
                        a = true;
                        return;
                    }
                }
                if (a) setTimeout(b, 100);
            }, 100);
        </script>
        <script>
            (function(){
                function appendAsyncScript(code, src = false, dependsOn = []){
                    let dependsLoaded = true;
                    dependsOn.forEach(globalObj => {
                        if (window[globalObj] == undefined) dependsLoaded = false;
                    });
                    if (dependsLoaded){
                        var el = {
                            name: 'script',
                            html : code.toString(),
                            attribs: {
                                type : 'text/javascript',
                                async : '',
                                defer : '',
                            }
                        };
                        if(src) el.attribs.src = src;
                        __app.appendToBody(__app.createElement(el));
                    } else setTimeout(() => {
                        appendAsyncScript(code, src, dependsOn);
                    }, 100);
                };
                <?php echo json_encode($parts); ?>.forEach(function(e){
                    <?php
                        if (_USER_DEBUG_MODE || JS_LOADER_CHANNEL == 'beta'){
                    ?>
                            if(/^https:\/\/raw.githubusercontent.com\//.test(e)){
                                var xhr = new XMLHttpRequest();
                                xhr.open('GET', e, true);
                                xhr.send();
                                xhr.onreadystatechange = function(){
                                    if (xhr.readyState != 4) return;
                                    if (xhr.status == 200){
                                        appendAsyncScript(xhr.responseText, false, e.deps);
                                    }
                                }
                            } else <?php
                        }
                    ?> appendAsyncScript('', e.src, e.deps);
                });
            })();
        </script>
        <?
    });
})();

?>