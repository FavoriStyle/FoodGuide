<?php

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

    class eaDB{
        private static function getTaxsLangsIds($tax, $filter = false){
            $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => false]);
            if(is_wp_error($terms)){
                return $terms -> get_error_message();
            }
            foreach ($terms as $i => $value){
                $query_result = staticGlobals::mysql_result('SELECT `description` FROM `term_taxonomy` WHERE `taxonomy` = \'term_translations\' AND `description` LIKE \'%";i:' . $value -> term_id . ';%\'');
                if ($query_result){
                    $a = unserialize($query_result[0]['description']);
                    $terms[$i] = [
                        ($value -> term_id == $a['en']) ? $value : get_term_by('id', $a['en'], $tax),
                        ($value -> term_id == $a['ru']) ? $value : get_term_by('id', $a['ru'], $tax),
                        ($value -> term_id == $a['uk']) ? $value : get_term_by('id', $a['uk'], $tax),
                    ];
                } else unset($terms[$i]);
            }
            if ($filter) return $filter($terms);
            return $terms;
        }
        private static function categoriesOrganizer($obj, $parent_id = 0){
            $tmp_obj = new stdClass();
            foreach ($obj as $name => $category){
                if($category -> parent === $parent_id){
                    $tmp_obj -> $name = $category;
                }
            }
            foreach ($obj as $name => $category){
                foreach ($tmp_obj as $tmp_obj_name => $tmp_obj_category){
                    if ($tmp_obj_category -> id == $category -> parent){
                        $tmp_obj -> $tmp_obj_name -> childs = self::categoriesOrganizer($obj, $category -> parent);
                    }
                }
            }
            return $tmp_obj;
        }
        public static function getFilters(){
            $filter_list = self::getTaxsLangsIds('ait-items_filters', function($terms){
                foreach ($terms as $i => $value){
                    foreach ($value as $i1 => $value1){
                        $terms[$i][$i1] -> icon = get_option($tax . '_category_' . $value1 -> term_id)["icon"];
                        if ($terms[$i][$i1] -> icon == '') $terms[$i][$i1] -> icon = '/wp-content/themes/foodguide/design/img/check.png';
                    }
                }
                return $terms;
            });
            $obj = new stdClass();
            $lang_index = _x('0', 'ea_pages_new [lang index]', 'ait-admin') * 1;
            foreach ($filter_list as $filter){
                $obj2 = new stdClass();
                $obj2 -> id = $filter[0] -> term_id;
                $obj2 -> icon = $filter[$lang_index] -> icon;
                $name = $filter[$lang_index] -> name;
                $obj -> $name = $obj2;
            }
            return json_encode($obj);
        }
        public static function getCategories(){
            $cat_list = self::getTaxsLangsIds('ait-items');
            $obj = new stdClass();
            $lang_index = _x('0', 'ea_pages_new [lang index]', 'ait-admin') * 1;
            foreach ($cat_list as $category){
                $obj2 = new stdClass();
                $obj2 -> id = $category[0] -> term_id;
                $obj2 -> parent = $category[0] -> parent;
                $name = $category[$lang_index] -> name;
                $obj -> $name = $obj2;
            }
            foreach ($obj as $name => $category){
                $obj -> $name -> childs = new stdClass();
            }
            return json_encode(self::categoriesOrganizer($obj));
        }
    }

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
   
?>