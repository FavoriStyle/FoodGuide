<?php

    $FontAwesome = new class{
        private $stack = [];
        private $colors_stack = [];
        private $colors = [
            'blue' => '#0035ff',
        ];
        public function __get($name){
            if(!in_array($name, $this -> stack) && !isset($this -> colors[$name])) $this -> stack[] = $name; elseif(isset($this -> colors[$name])){
                if (!in_array($name, $this -> colors_stack)) $this -> colors_stack[] = $name;
                return " dashicons-fa-color-$name";
            }
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
                foreach($this -> colors_stack as $color){
                    $css .= ".dashicons-fa-color-$color:before{color:" . $this -> colors[$color] . " !important}";
                }
                echo "<style>$css</style>";
            });
        }
    };

    $menu = new class{
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
                    'iscurrent' => (function($a) use ($href){
                        return !!preg_match('/' . str_replace('/', '\\/', preg_quote($href)) . '([&\\?].*)?$/', $a);
                    })($_SERVER['REQUEST_URI']),
                    'childs' => []
                ]) - 1;
            }
        }
        public function generate_menu(){
            $str = '';
            $class_list = [
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
            foreach ($this -> menu as $element){
                $element['childs_count'] = count($element['childs']);
                $str .= '<li class="' . (function($current) use ($class_list){
                    if ($current) return implode(' ', $class_list['active']); else return implode(' ', $class_list['non-active']);
                })($element['iscurrent']) .' wp-has-submenu" id="toplevel_page_items_page_new">
                            <a href="' . $element['href'] . '" aria-haspopup="false">
                                <div class="wp-menu-arrow"><div></div></div>
                                <div class="wp-menu-image dashicons-before ' . $element['icon'] . '"><br></div>
                                <div class="wp-menu-name">' . $element['header'] . '</div></a>' . (function() use ($element){
                                    $str = '';
                                    if($element['childs_count']){
                                        $str = '<ul class="wp-submenu wp-submenu-wrap"><li class="wp-submenu-head" aria-hidden="true">' . $element['header'] . '</li>';
                                        foreach ($element['childs'] as $i => $child){
                                            $first = (!$i ? ' class="wp-first-item"' : '');
                                            $str .= '<li' . $first . '><a href="' . $child['href'] . '"' . $first . '>' . $child['header'] . '</a></li>';
                                        }
                                        $str .= '</ul>';
                                    }
                                    return $str;
                                })($element['childs'], $element['iscurrent']);
                $str .= '</li>';
            }
            return $str;
        }
    };
    add_filter('easyadmin_addon', function($output) use (&$menu){
        return preg_replace('/<li[^>]*\\sid="collapse-menu"[^>]*>[\\s\\S]*?<\\/li>/', $menu -> generate_menu() . '$0', $output);
    });
    $parent = $menu -> addMenu('Items new', '/wp-admin/profile.php', implode(' ', [$FontAwesome -> f00b, $FontAwesome -> blue]));
    $menu -> addMenu('Сабзиро', '#этовсёчтоунегоможетбыть', null, $parent);
    $menu -> addMenu('Сабзиро2', '#этовсёчтоунегоможетбыть2', null, $parent);
   
?>