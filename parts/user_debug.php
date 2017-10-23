<?php
    (function(){
        define('_USER_DEBUG_MODE', (isset($_GET['--beta']) || isset($_GET['--debug']) || isset($_GET['--devel'])));
        if(_USER_DEBUG_MODE){
            add_action('wp_enqueue_scripts', function(){
                ?>
                    <script>
                        window.onerror = (msg, url, lno)=>{
                            console.log('На странице произошла восстановимая ошибка. Чтобы увидеть список ошибок, перейдите в режим отладки. ' + location.origin + '/?--debug');
                            return true;
                        }
                    </script>
                <?php
            });
        }
    })();
?>