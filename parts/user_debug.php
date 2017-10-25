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
        }
    })();
?>