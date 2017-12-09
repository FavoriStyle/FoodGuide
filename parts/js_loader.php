<?php

/*
Plugin Name: FGC OpenParts javascript loader
Description: Adds avaiability to load external JS code from jsdelivr hosted on GitHub
Plugin URI: https://github.com/FavoriStyle/FoodGuide/
Version: 0.0.1-a
Author: KaMeHb-UA
Author URI: https://github.com/KaMeHb-UA
License: MIT
*/

(function(){

    define('JS_LOADER_CHANNEL', 'beta'); // beta or stable

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
    $debug_parts = [
        //parts to be loaded only with --debug or --devel key
    ];

    if(_USER_DEBUG_MODE){
        foreach ($debug_parts as $part){
            $parts[] = $part;
        }
    }
    foreach($parts as $i => $part){ // https://raw.githubusercontent.com/FavoriStyle/FoodGuide/master/parts/api.php
        if (_USER_DEBUG_MODE || JS_LOADER_CHANNEL == 'beta') $part['src'] = "https://raw.githubusercontent.com/$settings[user]/$settings[repo]/master/assets/js/$part[src].js"; else $part['src'] = "https://cdn.jsdelivr.net/gh/$settings[user]/$settings[repo]@" . staticGlobals::getCurrentGitHubRelease() . "/assets/js/$part[src].min.js";
        $parts[$i] = $part;
    }
    add_action('wp_enqueue_scripts', function() use ($parts){
        ?>
        <script>
            (function(){
                let app = {};
                app.waitForBody = function(func, args = []){
                    setTimeout(function(){
                        if(document.body) func.apply(document, args); else app.waitForBody(func, args);
                    }, 100);
                };
                app.appendToBody = function(DOMElement){
                    try{
                        document.body.appendChild(DOMElement);
                    } catch(e) {
                        app.waitForBody(function(){
                            document.body.appendChild(DOMElement);
                        })
                    }
                };
                app.extend = function(oldObj, newObj){
                    for(var i in newObj){
                        oldObj[i] = newObj[i];
                    }
                    return oldObj;
                };
                app.createElement = function(settings){
                    settings = app.extend({
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
                        app.appendToBody(app.createElement(el));
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