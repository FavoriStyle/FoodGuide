<?php

    $FontAwesome = new class{
        private $stack = [];
        private $colors_stack = [];
        private $colors = [
            'red' => '#ff0000',
            'green' => '#00ff00',
            'blue' => '#0000ff',
        ];
        public function __get($name){
            if(!in_array($name, $this -> stack) && !isset($this -> colors[$name])) $this -> stack[] = $name; elseif(isset($this -> colors[$name])){
                if (!in_array($name, $this -> colors_stack)) $this -> colors_stack[] = $name;
                return "dashicons-fa-color-$name";
            }
            return "dashicons-fa-u-$name";
        }
        public function __construct(){
            add_action('admin_enqueue_scripts', function(){
                $css = '';
                foreach($this -> stack as $symbol){
                    $css .= ",.dashicons-fa-special-holder.dashicons-fa-u-$symbol:before";
                }
                $css = mb_substr($css, 1) . '{font-family:FontAwesome !important}';
                foreach($this -> stack as $symbol){
                    $css .= ".dashicons-fa-special-holder.dashicons-fa-u-$symbol:before{content:\"\\$symbol\"}";
                }
                foreach($this -> colors_stack as $color){
                    $css .= ".dashicons-fa-special-holder.dashicons-fa-color-$color:before{color:" . $this -> colors[$color] . " !important}";
                }
                echo "<style>$css</style>";
            });
        }
    };

    $menu = new class{
        private $menu = [];
        private $callbacks = [];
        public function addMenu($header, $href, $callback, $classes, $parent = -1){
            $cbname = 'callback_' . (array_push($this -> callbacks, $callback) - 1);
            if($parent + 1){
                array_push($this -> menu[$parent]['childs'], [
                    'header' => $header,
                    'href' => $href,
                    'callback' => $cbname
                ]);
            } else {
                return array_push($this -> menu, [
                    'header' => $header,
                    'href' => $href,
                    'icon' => 'dashicons-fa-special-holder',
                    'classes' => $classes,
                    'childs' => [],
                    'callback' => $cbname
                ]) - 1;
            }
        }
        public function __call($method, $args){
            $i = [];
            if(preg_match('/^callback_(\\d)+$/', $method, $i)) echo $this -> callbacks[$i[1]](); else call_user_func_array($this -> $method, $args);
        }
        public function __construct(){
            $stack = [];
            add_action('admin_menu', function() use (&$stack){
                foreach ($this -> menu as $element){
                    $stack[] = [$element['header'], $element['classes']];
                    add_menu_page($element['header'], $element['header'], /* capability */ 'read', $element['href'], [$this, $element['callback']], $element['icon']);
                    foreach($element['childs'] as $child){
                        add_submenu_page($element['href'], $child['header'], $child['header'], 'read', $child['href'], [$this, $child['callback']]);
                    }
                }
            });
            add_action('admin_enqueue_scripts', function() use (&$stack){
                ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        var a = document.getElementsByTagName('body')[0].classList, b = <?php echo json_encode($stack); ?>;
                        for(var i = 0; i < a.length; i++){
                            if (a[i] == 'ait-easy-admin-enabled'){
                                let i, a = document.querySelectorAll('#easyadmin-main-menu > li > a > .dashicons-fa-special-holder');
                                for(i = 0; i < a.length; i++){
                                    b.forEach(function(e){
                                        if(a[i].parentNode.lastChild.innerHTML == e[0]) e[1].forEach(function(cls){a[i].classList.add(cls)});
                                    });
                                }
                            }
                        }
                    });
                </script>
                <?php
            } , 10 , 2);
        }
    };

    $parent = $menu -> addMenu('Items new', 'new_page.ea_addon', function(){
        return 'Тупо кастомная страница';
    }, [$FontAwesome -> f00b, $FontAwesome -> blue]);
    $menu -> addMenu('List', 'new_page2.ea_addon', function(){
        return 'Тупо кастомная страница';
    }, null, $parent);
    $menu -> addMenu('Add new', 'new_page3.ea_addon', function(){
        return 'Тупо кастомная страница';
    }, null, $parent);
   
?>