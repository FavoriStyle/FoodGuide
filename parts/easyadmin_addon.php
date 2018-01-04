<?php
    function ea_add_action(){
        $args = func_get_args();
        $args[1] = function () use ($args){
            if (!current_user_can('use-native-admin-panel')) $args[1]();
        };
        call_user_func_array('add_action', $args);
    }
    class eaPage{
        private $text = '';
        public function __construct($tpl){
            $this -> text = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/FGC/templates/' . $tpl . '.tpl');
            return $this;
        }
        public function match($key, $target){
            $this -> text = implode($target, explode('{'.$key.'}', $this -> text));
            return $this;
        }
        public function matches($matches){
            foreach ($matches as $key => $value){
                $this -> text = implode($value, explode('{'.$key.'}', $this -> text));
            }
            return $this;
        }
        public function __toString(){
            return $this -> text;
        }
    }
    ea_add_action('admin_enqueue_scripts', function(){
        ?>
<style id="main-menu-loading-animation">
    #easyadmin-main-menu{
        height: 55.2px !important;
        overflow: hidden;
        padding-left: 20px;
        padding-top: 4px;
    }
    #easyadmin-main-menu > li:nth-child(1) > *{
        display: none;
    }
    #easyadmin-main-menu > li:nth-child(1),
    #easyadmin-main-menu > li:nth-child(1):before,
    #easyadmin-main-menu > li:nth-child(1):after {
      background: #ffffff;
      -webkit-animation: load1 1s infinite ease-in-out;
      animation: load1 1s infinite ease-in-out;
      width: 1em;
      height: 4em;
    }
    #easyadmin-main-menu > li:nth-child(1) {
      color: #ffffff;
      text-indent: -9999em;
      margin: 10px auto;
      position: relative;
      font-size: 8px;
      -webkit-transform: translateZ(0);
      -ms-transform: translateZ(0);
      transform: translateZ(0);
      -webkit-animation-delay: -0.16s;
      animation-delay: -0.16s;
    }
    #easyadmin-main-menu > li:nth-child(1):before,
    #easyadmin-main-menu > li:nth-child(1):after {
      position: absolute;
      top: 0;
      content: '';
    }
    #easyadmin-main-menu > li:nth-child(1):before {
      left: -1.5em;
      -webkit-animation-delay: -0.32s;
      animation-delay: -0.32s;
    }
    #easyadmin-main-menu > li:nth-child(1):after {
      left: 1.5em;
    }
    @-webkit-keyframes load1 {
      0%,
      80%,
      100% {
        box-shadow: 0 0;
        height: 4em;
      }
      40% {
        box-shadow: 0 -2em;
        height: 5em;
      }
    }
    @keyframes load1 {
      0%,
      80%,
      100% {
        box-shadow: 0 0;
        height: 4em;
      }
      40% {
        box-shadow: 0 -2em;
        height: 5em;
      }
    }
    #easyadmin-main-menu > *:not(:nth-last-child(2)):not(:nth-child(1)){
        display: none;
    }
</style>
<style>
    .notice,
    #your-profile > p:nth-of-type(1),
    #your-profile > p:nth-of-type(1) + h2,
    #your-profile > p:nth-of-type(1) + h2 + table,
    #your-profile > p:nth-of-type(1) + h2 + table + h3,
    #your-profile > p:nth-of-type(1) + h2 + table + h3 + table,
    #your-profile > table:nth-of-type(4) + h2,
    #your-profile > table:nth-of-type(5),
    #your-profile > h3:last-of-type,
    .post-type-attachment #postbox-container-2,
    .post-type-attachment .compat-attachment-fields,
    .attachment-info .compat-attachment-fields
    {
        display: none;
    }
    .media-frame-content .view-switch > a{
        max-width: 0;
        overflow: hidden;
    }
</style>
        <?php
    });
    $before_loaded_menu_css = "";
    $FontAwesome = new class{
        private $stack = [];
        private $colors_stack = [];
        private $colors = [
            'red' => '#ff0000',
            'green' => '#00ff00',
            'blue' => '#0000ff',
        ];
        public function __get($name){
            $check_color = function(&$str, $safe = false){
                if (mb_substr($str, 0, 7) == 'color||'){
                    if (!$safe){
                        $str = mb_substr($str, 7);
                        $setted_unique_codename = false;
                        while(!$setted_unique_codename){
                            $codename = substr(md5(uniqid(rand(), true)), 0, 5);
                            if (!isset($this -> colors[$codename])){
                                $this -> colors[$codename] = $str;
                                $setted_unique_codename = true;
                            }
                        }
                        $str = $codename;
                    }
                    return true;
                }
                return false;
            };
            if(!in_array($name, $this -> stack) && !isset($this -> colors[$name]) && !$check_color($name, true)) $this -> stack[] = $name; elseif(isset($this -> colors[$name]) || $check_color($name)){
                if (!in_array($name, $this -> colors_stack)) $this -> colors_stack[] = $name;
                return "dashicons-fa-color-$name";
            }
            return "dashicons-fa-u-$name";
        }
        public function __construct(){
            ea_add_action('admin_enqueue_scripts', function(){
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
            ea_add_action('admin_menu', function() use (&$stack){
                foreach ($this -> menu as $element){
                    $stack[] = [$element['header'], $element['classes']];
                    add_menu_page($element['header'], $element['header'], /* capability */ 'read', $element['href'], [$this, $element['callback']], $element['icon']);
                    foreach($element['childs'] as $child){
                        add_submenu_page($element['href'], $child['header'], $child['header'], 'read', $child['href'], [$this, $child['callback']]);
                    }
                }
            });
            ea_add_action('admin_enqueue_scripts', function() use (&$stack){
                ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        setTimeout(function(){
                            var a = document.getElementsByTagName('body')[0].classList, b = <?php echo json_encode($stack); ?>, c, d;
                            for(var i = 0; i < a.length; i++){
                                if (a[i] == 'ait-easy-admin-enabled'){
                                    c = true;
                                }
                            }
                            a = document.querySelectorAll('.dashicons-fa-special-holder');
                            if (c) d = function(a,i,e){
                                if(a[i].parentNode.lastChild.innerHTML == e[0]) e[1].forEach(function(cls){a[i].classList.add(cls)});
                            }; else d = function(a,i,e){
                                if(a[i].parentNode.lastChild.innerHTML == e[0]){
                                    a[i].parentNode.parentNode.remove();
                                }
                            };
                            for(i = 0; i < a.length; i++){
                                b.forEach(function(e){
                                    d(a,i,e);
                                });
                            }
                        }, 10);
                    });
                </script>
                <?php
            }, 10, 2);
        }
    };
    /*
    $parent = $menu -> addMenu('Items new', 'new_page.ea_addon', function(){
        return (new eaPage('ea_item_add')) -> matches([
            'heading'                           => __('Add New Item', 'ait-toolkit'),
            'Enter_item_name'                   => _x('Item name', 'ea_items_list_page_new', 'ait-admin'),
            'By_russian'                        => _x('By russian', 'ea_pages_new', 'ait-admin'),
            'By_ukrainian'                      => _x('By ukrainian', 'ea_pages_new', 'ait-admin'),
            'By_english'                        => _x('By english', 'ea_pages_new', 'ait-admin'),
            'Enter_russian_item_desc'           => _x('Enter russian item description', 'ea_pages_new', 'ait-admin'),
            'Enter_ukrainian_item_desc'         => _x('Enter ukrainian item description', 'ea_pages_new', 'ait-admin'),
            'Enter_english_item_desc'           => _x('Enter english item description', 'ea_pages_new', 'ait-admin'),
            'Select_or_upload_image'            => _x('Select or upload image', 'ea_pages_new', 'ait-admin'),
            'Set_image'                         => _x('Set image', 'ea_pages_new', 'ait-admin'),
            'Upload_item_image'                 => _x('Select image', 'ea_pages_new', 'ait-admin'),
            'Item_image'                        => _x('Item image', 'ea_pages_new', 'ait-admin'),
            'Select_or_upload_images'           => _x('Select or upload images', 'ea_pages_new', 'ait-admin'),
            'Set_images'                        => _x('Set images', 'ea_pages_new', 'ait-admin'),
            'Upload_item_gallery_images'        => _x('Select images', 'ea_pages_new', 'ait-admin'),
            'Item_gallery'                      => _x('Item gallery', 'ea_pages_new', 'ait-admin'),
            'single_upload_image_notice'        => _x('* to change your choose just select image once more time', 'ea_pages_new', 'ait-admin'),
            'multiple_upload_images_notice'     => _x('* to change your choose just select images once more time', 'ea_pages_new', 'ait-admin'),
            'Item'                              => _x('Item', 'post type singular name', 'ait-toolkit'),
            'Map_header'                        => _x('Item\'s location', 'ea_pages_new', 'ait-admin'),
            'Work_time'                         => _x('Work time', 'ea_pages_new', 'ait-admin'),
            'time-start_placeholder'            => _x('Start', 'ea_pages_new [work time]', 'ait-admin'),
            'time-end_placeholder'              => _x('End', 'ea_pages_new [work time]', 'ait-admin'),
            'pause-start_placeholder'           => _x('Start', 'ea_pages_new [work time]', 'ait-admin'),
            'pause-end_placeholder'             => _x('End', 'ea_pages_new [work time]', 'ait-admin'),
            'Day_of_week'                       => _x('Day of week', 'ea_pages_new', 'ait-admin'),
            'Work_time_figurale'                => _x('Work time', 'ea_pages_new [figurale]', 'ait-admin'),
            'Rest_time_figurale'                => _x('Rest time', 'ea_pages_new [figurale]', 'ait-admin'),
            'checkbox_text_No'                  => _x('No', 'ea_pages_new', 'ait-admin'),
            'checkbox_text_Yes'                 => _x('Yes', 'ea_pages_new', 'ait-admin'),
            'Rest_day'                          => _x('Rest day', 'ea_pages_new', 'ait-admin'),
            'All_day'                           => _x('All day', 'ea_pages_new [work time]', 'ait-admin'),
            'additional_filters_option_list'    => eaDB::getFilters(),
            'item_categories_option_list'       => eaDB::getCategories(),
            'Additional_services'               => _x('Additional services', 'ea_pages_new', 'ait-admin'),
            'Additional_services_tip'           => _x('Set your item\'s services', 'ea_pages_new', 'ait-admin'),
            'Item_categories'                   => _x('Item categories', 'ea_pages_new', 'ait-admin'),
            'Item_categories_tip'               => _x('Set your item\'s categories', 'ea_pages_new', 'ait-admin'),
            'Publish'                           => _x('Publish', 'ea_pages_new', 'ait-admin'),
            'Publish_button_text'               => _x('Save', 'ea_pages_new [publish button text]', 'ait-admin'),
            'monday_json'                       => json_encode(_x('monday', 'ea_pages_new [days of week]', 'ait-admin')),
            'tuesday_json'                      => json_encode(_x('tuesday', 'ea_pages_new [days of week]', 'ait-admin')),
            'wednesday_json'                    => json_encode(_x('wednesday', 'ea_pages_new [days of week]', 'ait-admin')),
            'thursday_json'                     => json_encode(_x('thursday', 'ea_pages_new [days of week]', 'ait-admin')),
            'friday_json'                       => json_encode(_x('friday', 'ea_pages_new [days of week]', 'ait-admin')),
            'saturday_json'                     => json_encode(_x('saturday', 'ea_pages_new [days of week]', 'ait-admin')),
            'sunday_json'                       => json_encode(_x('sunday', 'ea_pages_new [days of week]', 'ait-admin')),
            'Check_your_item_address'           => str_replace('{what_to_check}', _x('adress', 'ea_pages_new [responces]', 'ait-admin'), _x('Check your item\'s {what_to_check}', 'ea_pages_new [responces]', 'ait-admin')),
            //'location_list'                     => json_encode(eaDB::construct_locations()),
            'english_item_desc_tip'             => _x('Describe your item in english', 'ea_pages_new', 'ait-admin'),
            'russian_item_desc_tip'             => _x('Describe your item in russian', 'ea_pages_new', 'ait-admin'),
            'ukrainian_item_desc_tip'           => _x('Describe your item in ukrainian', 'ea_pages_new', 'ait-admin'),
            'Item_email'                        => _x('Item email', 'ea_pages_new', 'ait-admin'),
            'Item_email_tip'                    => _x('Item email tip', 'ea_pages_new', 'ait-admin'),
            'Item_phones'                       => _x('Item phones', 'ea_pages_new', 'ait-admin'),
            'Item_phones_tip'                   => _x('Item phones tip', 'ea_pages_new', 'ait-admin'),
            'Add_new_phone'                     => _x('Add new phone', 'ea_pages_new', 'ait-admin'),
            'Item_check'                        => _x('Average check', 'ea_pages_new', 'ait-admin'),
            'Item_check_tip'                    => _x('Average check tip', 'ea_pages_new', 'ait-admin'),
            'UAH'                               => _x('UAH', 'ea_pages_new', 'ait-admin'),
            'Item_location'                     => _x('Item location', 'ea_pages_new', 'ait-admin'),
            'Item_location_tip'                 => _x('Item location tip', 'ea_pages_new', 'ait-admin'),
            'Item_categories_main_cat_tip'      => _x('Main category tip', 'ea_pages_new', 'ait-admin'),
        ]);
    }, [$FontAwesome -> f00b, $FontAwesome -> blue]);
    $menu -> addMenu('List', 'new_page2.ea_addon', function(){
        return 'Тупо кастомная страница';
    }, null, $parent);
    $menu -> addMenu('Add new', 'new_page3.ea_addon', function(){
        return 'Тупо кастомная страница';
    }, null, $parent);
    */
    $fa = function() use (&$FontAwesome){
        $arr = func_get_args();
        foreach ($arr as $key => $value){
            $arr[$key] = $FontAwesome -> $value;
        }
        return 'dashicons-fa-special-holder ' . implode(' ', $arr);
    };
    $queue = [ // Приоретизация пунктов главного меню
        'profile.php'                           => $fa('color||#898700'),           // Профиль
        'upload.php'                            => $fa('color||#649b7c'),           // Медиа
        'admin.php?page=items_page_new'         => $fa('color||#7a7ae3'),           // Заведения
        'edit.php?post_type=ait-special-offer'  => $fa('f005', 'color||#ff9f03'),   // Акции
        'edit.php?post_type=ait-food-menu'      => $fa('f0f5', 'color||#b75c5c'),   // Меню
        'edit.php?post_type=ait-ad-space'       => $fa('f1ea', 'color||#cb3ecf'),   // Афиша
        'edit.php',                                                                 // Блог
        'admin.php?page=helpme',                                                    // Помощь
    ];
    ea_add_action('admin_menu', function(){
        remove_submenu_page('upload.php', 'wp-smush-bulk');
        remove_menu_page('edit-comments.php');
        remove_menu_page('tools.php');
        remove_menu_page('edit.php');
    }, 9999);
    ea_add_action('admin_enqueue_scripts', function() use ($queue){
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                var priorities = <?php
                    $temp = [];
                    foreach ($queue as $key => $value){
                        if (gettype($key) == "integer"){
                            $temp[] = $value;
                        } else {
                            $temp[] = $key;
                        }
                    }
                    foreach ($temp as $key => $value){
                        unset($temp[$key]);
                        $temp[$value] = $key;
                    }
                    echo json_encode($temp);
                ?>, sorted = [], list = document.querySelectorAll('#easyadmin-main-menu > li'), customClass = <?php
                    $temp = [];
                    foreach ($queue as $key => $value){
                        if (gettype($key) == "string"){
                            $temp[$key] = $value;
                        }
                    }
                    echo json_encode($temp);
                ?>;
                for(var i = 0; i < list.length; i++){
                    if (list[i].children[0].attributes.href){
                        if (priorities[list[i].children[0].attributes.href.nodeValue] !== undefined){
                            sorted[priorities[list[i].children[0].attributes.href.nodeValue]] = list[i];
                        }
                        if (customClass[list[i].children[0].attributes.href.nodeValue] && list[i].children[0].children[1]){
                            list[i].children[0].children[1].className += " " + customClass[list[i].children[0].attributes.href.nodeValue];
                            if(list[i].children[0].children[1].children[0]) list[i].children[0].children[1].children[0].remove();
                        }
                    }
                }
                sorted = sorted.reverse();
                sorted.forEach((e) => {
                    if (e){
                        list[0].parentNode.prepend(e);
                    }
                });
                document.getElementById('main-menu-loading-animation').remove();
            });
        </script>
        <?php
    });
?>