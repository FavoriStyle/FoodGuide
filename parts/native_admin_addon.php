<?php

    $utf8 = function($str){
        return iconv(mb_detect_encoding($str, mb_detect_order(), true), "UTF-8", $str);
    };

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

    add_action('admin_bar_menu', function($wp_admin_bar) /* добавляем новое топбар-меню */ {
        $args = [
            'id'    => 'remove-openparts-cache',
            'title' => 'Remove OpenParts cache',
            'href'  => '/wp-content/mu-plugins/openparts.php?--remove-cache&secret=' . urlencode(Secrets::$delete_cache_secret),
            'meta'  => [
                'onclick' => 'var xhr = new XMLHttpRequest(); xhr.open("GET", this.getAttribute("href"), true); var innerHTML = this.innerHTML, _this = this; xhr.send(); this.innerHTML = "Clearing..."; xhr.onreadystatechange = function(){ if (xhr.readyState != 4) return; if (xhr.status == 200){if(confirm("OpenParts cache cleared. Reload the page?")) location.href = location.href; else {_this.innerHTML = "Cleared"; _this.setAttribute("href", "#"); _this.onclick = null}}}; return false;'
            ]
        ];
        $wp_admin_bar->add_node($args);
    }, 999);

    $templates = new class{
        private $cache = [];
        public function __get($name){
            if(!isset($this -> cache[$name])) $this -> cache[$name] = new class($name){
                private $str = '';
                public function __construct($name){
                    $this -> str = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/FGC/templates/' . $name . '.tpl');
                    $this -> str = ($this -> str ? $this -> str : '');
                }
                public function __toString(){
                    return $this -> str;
                }
                public function set($what, $val){
                    $this -> str = implode($val, explode('{' . $what . '}', $this -> str));
                }
            };
            return $this -> cache[$name];
        }
    };

    add_action('admin_menu', function() use (&$FontAwesome, &$templates, $utf8){
        /*must be initialized for pseudoasync callback first*/ $FontAwesome -> f0c7;
        add_menu_page('Multiple -> Single title', 'Multiple -> Single', 'loco_admin', 'multiple-single-custom-matcher', function() use (&$FontAwesome, &$templates, $utf8){
            $templates -> multiple_to_single_matching -> set('heading', __('Multiple and single categories names matching', 'ait-admin'));
            $templates -> multiple_to_single_matching -> set('mtsm_tip', 'Tip will be here');
            $temp = staticGlobals::mysql_result('SELECT * FROM `categories_singles`', new class{
                public function log($a){
                    //var_dump($a);
                }
                public function warn($a){
                    //var_dump($a);
                }
                public function error($a){
                    //var_dump($a);
                }
            });
            foreach ($temp as $i => $row){
                $temp[$utf8($row['category'])] = $utf8($row['single']);
                unset($temp[$i]);
            }
            foreach(staticGlobals::mysql_result('SELECT terms.name FROM `terms` as terms JOIN `term_taxonomy` as tax WHERE tax.taxonomy = \'ait-items\' AND terms.term_id = tax.term_id' /* . ' AND tax.parent = 0' */) as $row){
                $row['name'] = $utf8($row['name']);
                $row['name'] = mb_strtoupper(mb_substr($row['name'], 0, 1)) . mb_strtolower(mb_substr($row['name'], 1));
                if(!isset($temp[$row['name']])) $temp[$row['name']] = '';
            }
            $templates -> multiple_to_single_matching -> set('main_matching', json_encode($temp));
            $templates -> multiple_to_single_matching -> set('fa-save', $FontAwesome -> f0c7);
            //var_dump($temp);
            echo $templates -> multiple_to_single_matching;
        }, $FontAwesome -> f145);
        //

        //
        add_menu_page('Node-notifier title', 'Node-notifier', 'loco_admin', 'node-notifier', function() use (&$FontAwesome, &$templates, $utf8){
            function update_settings($settings){
                return (function($sql){
                    $sql = preg_replace_callback('/(UPDATE)\s+`(.+?)`/ms', function($matches){
                        global $wpdb;
                        if(!(mb_strpos($matches[2], $wpdb -> prefix) === 0)) $matches[2] = $wpdb -> prefix . $matches[2];
                        return $matches[1] . ' `' . $matches[2] . '`';
                    }, $sql);
                    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                    if (!$mysqli -> connect_errno){
                        $mysqli -> query($sql);
                        return $mysqli -> affected_rows;
                    }
                    return false;
                })('UPDATE `node-notificator` SET parameters = FROM_BASE64("' . base64_encode(json_encode($settings)) . '") WHERE login = " "') == 1;
            }

            function normalize_json($json){ // Дабы при реверсии числа случаем 0 впереди не выскочил
                if (!(mb_strlen($json) % 10)){
                    $json = substr($json, 0, -1) . ' }';
                }
                return $json;
            }

            function getSettings(){
                return json_decode(staticGlobals::utf8(staticGlobals::mysql_result('SELECT parameters FROM `node-notificator` WHERE login = " "')[0]['parameters']), true);
            }

            if(isset($_GET['save-table'])){
                $json = json_encode((function(){
                    $settings = getSettings();
                    foreach($_POST['data'] as $event => $users){
                        if (isset($settings['eventdef'][$event])){
                            $settings['eventdef'][$event]['users'] = $users;
                        }
                    }
                    return ['updated' => update_settings($settings)];
                })());
                $json = normalize_json($json);
                die($json . mb_strlen($json));
            }

            if(isset($_GET['add-event'])){
                $json = json_encode((function(){
                    $settings = getSettings();
                    if (isset($settings['eventdef'][$_POST['name']])) return false;
                    $settings['eventdef'][$_POST['name']] = $settings['eventTpl'];
                    if($_POST['message'] && $_POST['message'] != '') $settings['eventdef'][$_POST['name']]['message'] = $_POST['message'];
                    return ['updated' => update_settings($settings)];
                })());
                $json = normalize_json($json);
                die($json . mb_strlen($json));
            }

            $templates -> node_notifier -> set('heading', __('Multiple and single categories names matching', 'ait-admin'));
            $user_props = (function($unsorted){
                $tmp = [];
                foreach($unsorted as $row){
                    $tmp[staticGlobals::utf8($row['login'])] = json_decode(staticGlobals::utf8($row['parameters']), true);
                }
                return $tmp;
            })(staticGlobals::mysql_result('SELECT * FROM `node-notificator`'));
            $events = [];
            foreach($user_props[' ']['eventdef'] as $event => $ev_props){
                $events[$event] = md5($event);
            }
            $users = staticGlobals::mysql_result('SELECT user_login FROM `users`');
            foreach($users as $i => $user){
                $users[$i] = staticGlobals::utf8($user['user_login']);
            }
            $contents = "<tr><th><div><span>Логин</span></div></th>";
            foreach($events as $event_display_text => $event){
                $contents .= "<th><div><span>$event_display_text</span></div></th>";
            }
            $templates -> node_notifier -> set('table-header', $contents . '</tr>');
            $contents = '';
            foreach($users as $login){
                $contents .= "<tr><td><div class=\"target-type-icon\" type=\"" . (isset($user_props[$login]) && isset($user_props[$login]['primary_receiver']) ? $user_props[$login]['primary_receiver'] : 'email') . "\"></div>$login</td>";
                foreach($events as $event_display_text => $event){
                    $contents .= "<td><input type=\"checkbox\" name=\"$login:::$event\" id=\"$login:::$event\"";
                    if (isset($user_props[' ']['eventdef'][$event_display_text]) &&
                        isset($user_props[' ']['eventdef'][$event_display_text]['users']) &&
                        in_array($login, $user_props[' ']['eventdef'][$event_display_text]['users'])
                    ) $contents .= ' checked="checked"';
                    $contents .= "><label for=\"$login:::$event\"></label></td>";
                }
                $contents .= '</tr>';
            }
            $templates -> node_notifier -> set('table-content', $contents);
            $css = '';
            foreach($user_props[' ']['typedef'] as $type => $typedef){
                $css .= "div.target-type-icon[type=\"$type\"]{
                    background-image: url($typedef[icon])
                }
                ";
            }
            $templates -> node_notifier -> set('additional-styles', $css);
            echo $templates -> node_notifier;
        }, $FontAwesome -> f0f3);
    });
   
?>