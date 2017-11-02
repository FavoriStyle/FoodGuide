<?php
    (function(){
        if(!_USER_DEBUG_MODE){
            add_action('wp_enqueue_scripts', function(){
                ?>
                    <script>
                        (function(){
                            function errorLog(msg, url, lno){
                                console.log('На странице произошла восстановимая ошибка. Чтобы увидеть список ошибок, перейдите в режим отладки. ' + location.origin + '/?--debug');
                                return true;
                            }
                            window.onerror = errorLog;
                            var a = {};
                            a.error = console.error;
                            console.error = function(){
                                errorLog.apply(console, arguments);
                                //a.error.apply(console, arguments);
                            };
                        })();
                    </script>
                <?php
            });
        } else {
            add_action('wp_enqueue_scripts', function(){
                ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function waitForJQuery(){
                            if(!window.jQuery) setTimeout(waitForJQuery, 10); else document.dispatchEvent(new Event('jQuery loaded', {}));
                        });
                        document.addEventListener('DOMContentLoaded', function(){
                            var a = document.getElementsByTagName('a');
                            for (var i in a){
                                var b = a[i].getAttribute('href');
                                a[i].setAttribute('href', (b.indexOf('?') + 1) ? b + '&--debug' : b + '?--debug');
                            }
                        });
                    </script>
                <?php
            });
        }
    })();
?>
