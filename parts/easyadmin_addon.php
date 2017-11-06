<?php

    $FontAwesome = new class{
        private $stack = [];
        public function __get($name){
            if(!in_array($name, $this -> stack)) $this -> stack[] = $name;
            return "dashicons-fa-u-$name";
        }
        public function __construct(){
            add_action('admin_enqueue_scripts', function(){
                $css = '';
                foreach($this -> stack as $symbol){
                    $css .= ",.dashicons-fa-u-$symbol:before";
                }
                $css = mb_substr($css, 1) . '{font-family:FontAwesome !important}';
                foreach($this -> stack as $symbol){
                    $css .= ".dashicons-fa-u-$symbol:before{content:\"\\$symbol\"}";
                }
                echo "<style>$css</style>";
            });
        }
    };

    $menu = new class{
        private $class_list = [
            'submenu' => [
                'wp-has-submenu',
            ],
            'wo_submenu' => [],
            'active' => [
                'wp-has-current-submenu',
            ],
            'non-active' => [
                'wp-not-current-submenu',
            ],
            'defaults' => []
        ];
        private $menu = [];
        public function addMenu($header, $href, $icon, $parent = -1){
            if($parent + 1){
                array_push($this -> menu[$parent]['childs'], [
                    'header' => $header,
                    'href' => $href
                ]);
            } else {
                return array_push($this -> menu, [
                    'header' => $header,
                    'href' => $href,
                    'icon' => $icon,
                    'childs' => []
                ]);
            }
        }
        public function __construct(){
            $test = $this -> addMenu('Тестовый заголовок', '/wp-admin/profile.php', $FontAwesome -> f145);
            $this -> addMenu('Сабзиро', '#этовсёчтоунегоможетбыть', null, $test);
            $this -> addMenu('Сабзиро2', '#этовсёчтоунегоможетбыть2', null, $test);
        }
        public function generate_menu(){
            $str = '';
            foreach ($this -> menu as $element){
                $str .= '<li class="wp-has-current-submenu wp-has-submenu" id="toplevel_page_items_page_new">
                            <a href="admin.php?page=items_page_new" aria-haspopup="false">
                                <div class="wp-menu-arrow"><div></div></div>
                                <div class="wp-menu-image dashicons-before dashicons-fa-u-' . $element['icon'] . '"><br></div>
                                <div class="wp-menu-name">' . $element['header'] . '</div>
                            </a>';
                $str .= '</li>';
            }
        }
    };
    add_filter('easyadmin_addon', function($output) use (&$menu){
        return preg_replace('/<li[^>]*\\sid="collapse-menu"[^>]*>[\\s\\S]*?<\\/li>/', $menu -> generate_menu . '$0', $output);
    });
   
?>