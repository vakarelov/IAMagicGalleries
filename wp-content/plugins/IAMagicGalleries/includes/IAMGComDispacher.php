<?php
/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

use IAMagicGalleries\Client;
use IAMagicGalleries\ImageHandler;

if (!defined('WPINC')) {
    exit;
}

//require_once IAMG_INCLUDES_PATH . "Gallery_Gen_Link.php";

class IAMGComDispacher
{

//    private $gen_link;
    private $slug = IAMG_SLUG;
    private $jsonData;

    static private $settings_defs = [
        'preserve_posts' => [
            'option' => 'preserve_posts_on_uninstall', //option name
            'type' => true, //type of value as example
            'autoload' => false
        ],
//        'test' => [
//            'option' => 'test_for_settings', //option name
//            'type' => "string", //type of value as example
//            'autoload' => false
//        ],
    ];

    function __construct()
    {
//        $basename = plugin_basename(__FILE__);
//        list($this->slug, $_) = explode('/', $basename);
//        $this->slug = IAMG_SLUG;

//        $this->gen_link = new Gallery_Gen_Link();

        add_action('wp_ajax_iamg_com', [$this, 'dispacher']);

        WP_DEBUG && add_action('wp_ajax_nopriv_iamg_com', [$this, 'dispacher']); //for debugging remove

        add_action('wp_ajax_iamg_app', [$this, 'load_app']);
        add_action('wp_ajax_iamg_builder_pres', [$this, 'builder_presentation']);
        add_action('wp_ajax_nopriv_iamg_app', [$this, 'load_app']);
        add_action('wp_ajax_iamg_pres', [$this, 'presentation']);
        add_action('wp_ajax_nopriv_iamg_pres', [$this, 'presentation']);

        add_filter('wp_prepare_attachment_for_js', [$this, '_attachment_monitor_filter']);
        add_filter('wp_insert_attachment_data', [$this, '_attachment_metadata_monitor_filter']);
//        add_action('save_post', [$this, 'save_post_monitor_filter']);
    }

    public function __call(string $name, array $arguments)
    {
        return null;
    }

    public function dispacher()
    {

        if (true || is_admin()) {
            $output = null;
            $command = $this->_get_command();
            if ($command && $command[0] !== '_' && method_exists($this, $command)) {
                $output = $this->$command();
                wp_send_json($output);
            }//		file_put_contents(__DIR__."/com.log", json_encode($output), FILE_APPEND);
            if ($output) {
                wp_send_json($output);
            }
        }


        wp_die();
    }

    private function _get_command()
    {
        return $this->_get_param('command');
    }

    private function _get_param($param)
    {
        if (isset($_POST[$param]) && $_POST[$param]) {
            return $_POST[$param];
        }
        if (isset($_GET[$param]) && $_GET[$param]) {
            return $_GET[$param];
        }

        if (!$this->jsonData) {
            $jsonData = file_get_contents("php://input");
            if ($jsonData) {
                $this->jsonData = json_decode($jsonData, true);
            }
            if (!$this->jsonData) {
                $this->jsonData = [null];
            }
        }
        if (isset($this->jsonData[$param])) {
            return $this->jsonData[$param];
        }

        return null;
    }

    public function load_app()
    {
        header('Content-Type: application/x-javascript; charset=utf-8');
        $client = new Client();
        $exp = $client->get_app_time();
        $cache_time = $exp - round(microtime(true));
        header("Cache-Control: max-age=" . $cache_time);
        $is_editor = is_admin() && (isset($_GET['editor']) || isset($_POST['editor']));
        echo $client->get_app($is_editor);
        wp_die();
    }

    function presentation()
    {
        $id = $this->_get_param("id");
        if ($id) {
//            echo $id;
//            $post = IAMG_posttype::get_post($id);
//
//            $content = ($post) ? get_post_meta($post->ID, "presentation", true) : null;

            $content = IAMG_posttype::get_post_presentation($id);

            if ($content) {
                header('Content-type: image/svg+xml');
//                header('Content-Type: text/plain');
                echo $content;
            }
        }

        wp_die();
    }

    function builder_presentation()
    {
        header('Content-type: image/svg+xml');
        $client = new Client();
//        echo file_get_contents(IAMG_PATH . 'resources/svg/admin.svg');
        echo $client->get_admin_presentation();
        wp_die();
    }

    function reset()
    {
        return ["hello" => "hi"];
    }

    function save()
    {
        //secure save
        $block_id = $this->_get_param('block_id');
        $pres_id = $this->_get_param('pres_id');
        $post_id = $this->_get_param('post_id');
        $page_id = $this->_get_param('page_id');
        $title = $this->_get_param('title');
        $locator = $this->_get_param('locator');
        $is_gallery_post = $this->_get_param('is_gallery_post');


        $user_id = get_current_user_id();

        if (false && !$pres_id) {
            $contents = "base64:" . base64_encode(file_get_contents(IAMG_PATH . "resources/svg/morph.svg"));
            $content = '<div id="' . $title . '" class="IA_Presenter_Container IA_Designer_Container" ' .
                'data-block-id="' . $block_id . '" presentation="' . $contents . '"></div>';

            $new_post = array(
                'post_title' => ($title) ?: "Magic Gallery",
                'post_content' => $content,
                'post_status' => 'draft',
                'post_author' => $user_id,
                'post_type' => IAMG_POST_TYPE,
//				'post_category' => array($categoryID)
            );

            $new_id = wp_insert_post($new_post);
            if ($new_id || !is_wp_error($new_id)) {
                $pres_id = (string)$new_id;
            }
        }


        if (!$pres_id) {
            $gallery_number = IAMG_posttype::get_gallery_count();
            $pres_id = "iamg_gallery_" . $gallery_number + 1;
        }

        if ($locator) {
            $settings = (new Client())->set_gallery_to_post($locator, $pres_id, $title, $block_id, $page_id, $post_id,
                $is_gallery_post);
            if ($settings === 'expired') {
                wp_send_json(['pres_id' => $pres_id, 'user_id' => $user_id, 'success' => false, 'status' => 'expired']);
            } elseif ($settings) {
                wp_send_json([
                    'pres_id' => $pres_id,
                    'user_id' => $user_id,
                    'success' => true,
                    'settings' => ImageHandler::sanitize($settings)
                ]);
            } else {
                wp_send_json(['pres_id' => $pres_id, 'user_id' => $user_id, 'success' => false, 'status' => 'error']);
            }
        }

        //just for testing
        wp_send_json(['pres_id' => $pres_id, 'user_id' => $user_id]);
    }

    function remove()
    {

    }

    function images()
    {
        $start = (int)$this->_get_param('start');
        $num_images = $this->_get_param('number_results');
        $album = $this->_get_param('album');


        if (!is_numeric($num_images)) {
            $num_images = null;
        } else {
            $num_images = (int)$num_images;
        }

        require_once IAMG_CLASSES_PATH . 'ImageHandler.php';

        $imageHandler = new ImageHandler();

        wp_send_json($imageHandler->get_for_library($start, $num_images, false, $album));
    }

    function videos()
    {
        $start = (int)$this->_get_param('start');
        $num_images = $this->_get_param('number_results');

        if (!is_numeric($num_images)) {
            $num_images = null;
        } else {
            $num_images = (int)$num_images;
        }

        require_once IAMG_CLASSES_PATH . 'ImageHandler.php';

        $imageHandler = new ImageHandler(true);

        wp_send_json($imageHandler->get_for_library($start, $num_images, true));
    }

    //make gallery
    function make_gallery()
    {

        $type = $this->_get_param("gallery_type");
        $images = $this->_get_param("images");
        $resource = $this->_get_param("resource");
        $settings = $this->_get_param("settings");

        if (!$settings) {
            $settings = [];
        }

        $imageHandler = new ImageHandler("all");

        $image_info = $imageHandler->get_for_gallery($images);

        $settings["type"] = $type;
        $settings["images"] = $image_info;
        $settings["requested_images"] = $images;
        if ($resource) {
            $settings["resource"] = $resource;
        }

//        return $settings;


        $client = new Client();

        $gallery = $client->get_gallery($settings);


        $result = [];
        if (isset($gallery['svg'])) {
            $result["svg"] = $gallery['svg'];
        }
        if (isset($gallery['locator'])) {
            $result['locator'] = $gallery['locator'];
        }
        if (isset($gallery['stored_settings'])) {
            $result['stored_settings'] = $gallery['stored_settings'];
        }
        if (isset($gallery['error'])) {
            $result['error'] = $gallery['error'];
        }
        if (!$gallery) {
            $result['error'] = 'No Response received from Server';
        }

        $result['settings'] = $settings;

        return $result;
    }

    private function _cast_to_same_type($source, $target)
    {
        switch (gettype($target)) {
            case 'boolean':
                $source = (bool)$source;
                break;
            case 'integer':
                $source = (int)$source;
                break;
            case 'float':
                $source = (float)$source;
                break;
            case 'string':
                $source = (string)$source;
                break;
            case 'array':
                $source = explode('+', $source);
                break;
            default:
        }
        return $source;
    }

    function settings()
    {
        $updated = [];
        foreach (self::$settings_defs as $sett => $def) {
            $setting_val = $this->_get_param($sett);
            if ($setting_val !== null) {
                $updated[$sett] = $setting_val;
                $autoload = !!$def['autoload'];
                $val = $this->_cast_to_same_type($setting_val, $def['type']);
                update_option($this->slug . '_' . $def['option'], $val, $autoload);
            }
        }
        return $updated;
    }

    public static function _get_setting_option_name($setting)
    {
        if (isset(self::$settings_defs[$setting])) {
            return IAMG_SLUG . '_' . self::$settings_defs[$setting]['option'];
        }
        return '';
    }

    //Monitors
    function _attachment_monitor_filter($post = null)
    {
        $this->_recordMediaChange($post['type']);
        return $post;
    }

    function _attachment_metadata_monitor_filter($data)
    {

        if ($data && isset($data['post_type']) && $data['post_type'] === 'attachment') {
            $mime = (isset($data['post_mime_type'])) ? $data['post_mime_type'] : "";
            $type = explode('/', $mime)[0];
            $this->_recordMediaChange($type);
        }

        return $data;
    }

    function _save_post_monitor_filter($id, $post)
    {
        $this->_recordMediaChange($post['type']);
    }

    /**
     * @param $type
     * @return void
     */
    private function _recordMediaChange($type): void
    {
        if ($type == "image") {
            if (get_option($this->slug . "_last_image_update")) {
                update_option($this->slug . "_last_image_update", microtime(true), '', false);
            } else {
                add_option($this->slug . "_last_image_update", microtime(true), '', false);
            }
        }
        if ($type == "video") {
            if (get_option($this->slug . "_last_video_update")) {
                update_option($this->slug . "_last_video_update", microtime(true), '', false);
            } else {
                add_option($this->slug . "_last_video_update", microtime(true), '', false);
            }
        }
    }
}

new IAMGComDispacher();