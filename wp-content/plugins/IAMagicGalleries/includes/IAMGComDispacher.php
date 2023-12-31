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

require_once __DIR__ . '/../src/autoload.php';

class IAMGComDispacher
{

//    private $gen_link;
    private $slug = IAMG_SLUG;
    private $jsonData;

    /**
     * @var array[] $settings_defs global settings for the plugin
     * //todo: move to separate class in future versions when more settings are available
     */
    static private $settings_defs = [
        'preserve_posts' => [
            'option' => 'preserve_posts_on_uninstall', //option name
            'type' => true, //type of value, as example
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

        add_action('wp_ajax_iamg_com', [$this, 'dispacher']);

        /**TESTING*/
        WP_DEBUG && add_action('wp_ajax_nopriv_iamg_com', [$this, 'dispacher']); //for debugging remove
        /**TESTING/*/

        add_action('wp_ajax_iamg_app', [$this, 'load_app']);
        add_action('wp_ajax_nopriv_iamg_app', [$this, 'load_app']);

        add_action('wp_ajax_iamg_builder_pres', [$this, 'builder_presentation']);

//        add_action('wp_ajax_iamg_pres', [$this, 'presentation']);
//        add_action('wp_ajax_nopriv_iamg_pres', [$this, 'presentation']);

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

        if (/**TESTING*/ true || /**TESTING/*/ is_admin()) {
            $output = null;
            $command = $this->_get_command();
            if ($command && $command[0] !== '_' && method_exists($this, $command)) {
                $output = $this->$command();
                wp_send_json($output);
            }
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
        //nonce is checked by the appropriate
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

    /**
     * Serve the IA Presenter app to client
     * @return void
     */
    public function load_app()
    {
        // This is a public endpoint that should be cached by browser, so need NOT verify a nonce
        // Client implements signature verification for this resource. See iaPresenter_loader.js

        $client = new Client();
        $exp = $client->get_app_time();
        $cache_time = $exp - round(microtime(true));
        header("Cache-Control: max-age=" . $cache_time);
        $is_editor = is_admin() && (isset($_GET['editor']) || isset($_POST['editor']));
        wp_send_json($client->get_app($is_editor));

        wp_die();
    }

    /**
     * Serve a presentation directly
     * Serve the IA Presenter app to client
     * @return void
     */
//    function presentation()
//    {
//        $id = $this->_get_param("id");
//        if ($id) {
//            $content = IAMG_posttype::get_post_presentation($id);
//
//            if ($content) {
//                header('Content-type: image/svg+xml');
////                header('Content-Type: text/plain');
//                echo $content;
//            }
//        }
//
//        wp_die();
//    }

    /**
     * Serv the Admin presentation for building galleries directly
     * @return void
     */
    function builder_presentation()
    {
        //plane text header
        header('Content-Type: text/plain');

        if (!$this->_verify_nonce(['iamg_block', 'iamg_admin_direct'])) {
            return "";
        }

        $client = new Client();

        //Snap_ia can load a presentation from base64 encoded LZ compressed string. Its needs to be prefixed with 'LZBase64:'
        echo 'LZBase64:' . $client->get_admin_presentation();
        wp_die();
    }

    function reset()
    {
        //todo
    }

    /**
     * Save a gallery to permanent storage from temporary storage, making it a post
     * @return void
     */
    function save()
    {
        if (!$this->_verify_nonce(['iamg_block', 'iamg_admin_direct'])) {
            wp_send_json(['error' => 'Access Denied']);
        }
        //secure save
        $block_id = $this->_get_param('block_id');
        $pres_id = $this->_get_param('pres_id');
        $post_id = $this->_get_param('post_id');
        $page_id = $this->_get_param('page_id');
        $title = $this->_get_param('title');
        $locator = $this->_get_param('locator');
        $is_gallery_post = $this->_get_param('is_gallery_post');


        $user_id = get_current_user_id();


        if (!$pres_id) {
            $gallery_number = IAMG_posttype::get_gallery_count();
            $pres_id = "iamg_gallery_" . $gallery_number + 1;
        }

        if ($locator) { // we should always have a locator
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

        //just for testing, we should never get here
//        wp_send_json(['pres_id' => $pres_id, 'user_id' => $user_id]);
    }

    function remove()
    {
        //for now the user will remove galleries from the admin panel
        // if we want to respond to removal of blocks containing galleries,
        //we will handle the removal here
    }

    /**
     * Send image to the library after request
     * @return void
     */
    function images()
    {
        if (!$this->_verify_nonce(['iamg_block', 'iamg_admin_direct'])) {
            return ['error' => 'Access Denied'];
        }

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

//        wp_send_json($imageHandler->get_for_library($start, $num_images, false, $album));
        return $imageHandler->get_for_library($start, $num_images, false, $album);
    }

    /**
     * Send video to the library after request
     * @return void
     */
    function videos()
    {
        if (!$this->_verify_nonce(['iamg_block', 'iamg_admin_direct'])) {
            return ['error' => 'Access Denied'];
        }

        $start = (int)$this->_get_param('start');
        $num_images = $this->_get_param('number_results');
        //todo: add albums

        if (!is_numeric($num_images)) {
            $num_images = null;
        } else {
            $num_images = (int)$num_images;
        }

        require_once IAMG_CLASSES_PATH . 'ImageHandler.php';

        $imageHandler = new ImageHandler(true);

//        wp_send_json($imageHandler->get_for_library($start, $num_images, true));
        return $imageHandler->get_for_library($start, $num_images, true);
    }

    /**
     * Gather images for gallery and send the request to generate it to the IA SAS
     * @return array
     */
    function make_gallery()
    {
        if (!$this->_verify_nonce(['iamg_block', 'iamg_admin_direct'])) {
            return ['error' => 'Access Denied'];
        }

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

    /**
     * Change global settings for the plugin
     * todo: primarily to future use
     * @return array
     */
    function settings()
    {
        if (!$this->_verify_nonce('iamg_settings')) {
            return ['error' => 'Access Denied'];
        }
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

    private function _verify_nonce($actions){
        $nonce = $this->_get_param('_nonce');
        if (!is_array($actions)) {
            $actions = [$actions];
        }
        for ($i = 0; $i < count($actions); $i++) {
            if (wp_verify_nonce($nonce, $actions[$i])) {
                return true;
            }
        }

        return false;
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