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
    $latest_release = OpenpartsCache::cache('gh_latest_release');
    if (!$latest_release){
        $latest_release = (function() use ($settings){
            $result = file_get_contents('https://api.github.com/graphql', false, stream_context_create([
                'http' => [
                    'header'  => "User-Agent: FoodGuide server-side API/0.1\r\nAuthorization: bearer " . Secrets::$github_graphql_token . "\r\n", // используем токен ограниченой функциональности. Ничего не умеет, ничего не знает... он просто есть, ибо требование
                    'method'  => 'POST',
                    'content' => json_encode([
                        'query' => '{repository(owner:' . json_encode($settings['user']) . ',name:' . json_encode($settings['repo']) . '){releases(last:1){edges{node{tag{name}}}}}}'
                    ])
                ]
            ]));
            if ($result && ($result = json_decode($result, true)) && !isset($result['errors'])){
                return $result['data']['repository']['releases']['edges'][0]['node']['tag']['name'];
            }
            return 'latest'; // emergency mode!!!
        })();
        OpenpartsCache::cache('gh_latest_release', $latest_release);
    }
    foreach($parts as $i => $part){
        $part['src'] = "https://cdn.jsdelivr.net/gh/$settings[user]/$settings[repo]@$latest_release/assets/js/$part[src].min.js";
        $parts[$i] = $part;
    }
    add_action('wp_enqueue_scripts', function() use ($parts){
        ?>
        <script>
            (function(){
                if (!window['app']) app = {};
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
                        if (_USER_DEBUG_MODE){
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