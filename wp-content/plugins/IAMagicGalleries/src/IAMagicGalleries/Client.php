<?php
/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

namespace IAMagicGalleries;


class Client
{
    const PLAYER_RESOURCES = "_player_resources";
    const EDITOR_RESOURCES = "_editor_resources";
    const APP_SCRIPT = "_app_script";
    const APP_SCRIPT_TIME = '_app_script_time';
    const APP_SCRIPT_UPDATED = '_app_script_updated';
    const APP_SCRIPT_BLOCKS = '_app_script_blocks';
    const APP_SCRIPT_EDITOR = "_app_script_editor";
    const APP_HAS_PRE_SCRIPT = '_app_has_pre_script';
    const APP_PRE_SCRIPT = "_app_pre_script";

    const ADMIN_EDITOR = '_admin_editor';

    const ADMIN_EDITOR_VERSION = '_admin_editor_version';
    const REGISTERED_SERVER = "_registered_server";

    const RESOURCE = '_resource_';
    const RESOURCE_VERSION = '_resource_version_';


    //ERROR conditions

    const ERROR_NO_ACCESS_TO_INTERNET = 1;
    const ERROR_SERVER_NOT_REACHABLE = 2; //cannot resolve ip address
    const ERROR_SERVER_NOT_AVAILABLE = 3;
    const ERROR_CONNECT_TIMEOUT = 4;
    const ERROR_CONNECT_ERROR = 5;

    /**
     * The client version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Name of the plugin
     *
     * @var string
     */
    public $name;

    /**
     * The plugin/theme file path
     * @example .../wp-content/plugins/test-slug/test-slug.php
     *
     * @var string
     */
    public $file;

    /**
     * Main plugin file
     * @example test-slug/test-slug.php
     *
     * @var string
     */
    public $basename;


    /**
     * The project version
     *
     * @var string
     */
    public $project_version;

    /**
     * The project type
     *
     * @var string
     */
    public $type;

    /**
     * textdomain
     *
     * @var string
     */
    public $textdomain;


    /**
     * The Object of Updater Class
     *
     * @var object
     */
    private $updater;


    private $routes = [
        "script" => "scripts.php",
        "register" => "register_iamg.php",
        "gallery" => "gallery.php"
    ];

    /**
     * Initialize the class
     *
     * @param string $name readable name of the plugin
     * @param string $file main plugin file path
     */
    public function __construct(
        $name = "IAMagicGalleries",
        $file = IAMG_PATH . "IAMagic-galleries.php"
    ) {
        $this->name = $name;
        $this->file = $file;

        $this->set_basename();
    }

    /**
     * Set project basename, slug and version
     *
     * @return void
     */
    protected function set_basename()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugin_data = get_plugin_data($this->file);

        $this->basename = plugin_basename(IAMG_PATH . "IAMagic-galleries.php");

        $this->project_version = $plugin_data['Version'];
        $this->type = 'plugin';

        $this->textdomain = IAMG_POST_TYPE;
    }

//    /**
//     * Initialize plugin/theme updater
//     *
//     * @return IAMG\Updater
//     */
//    public function updater()
//    {
//        if (!class_exists(__NAMESPACE__ . '\Updater')) {
//            require_once __DIR__ . '/Updater.php';
//        }
//
//        // if already instantiated, return the cached one
//        if ($this->updater) {
//            return $this->updater;
//        }
//
//        $this->updater = new Updater($this);
//
//        return $this->updater;
//    }
//
//    public function updateIAMG()
//    {
//
//    }

    public function get_app($editor = false)
    {
//        if (microtime(true) > $this->getAppTime()) {
//            // emergency system if for some reason a script has expired.
//            $this->updateAppScript();
//        }

        if ($editor) {
            return $this->get_editor_app();
        }

        $blocks = get_option(IAMG_SLUG . self::APP_SCRIPT_BLOCKS);

        if ($num_splits = get_option(IAMG_SLUG . "_app_script_split")) {
            $data = "";
            for ($i = 0; $i < $num_splits; ++$i) {
                $data .= get_option(IAMG_SLUG . "_app_script_" . $i);
            }
            return $this->decrypt(gzuncompress(base64_decode($data)), null, $blocks);
        }

        $lib = get_option(IAMG_SLUG . self::APP_SCRIPT);


        if (!$lib) {
            $result = $this->update_app_script();
            if ($result) {
                return $this->get_pre_app() . " " . $this->get_app();
            } else {
                return 'console.log("IA Presenter Application cannot be loaded!")';
            }
        }

        $gzuncompress = gzuncompress(base64_decode($lib));
//        return $blocks;
//        return $blocks . PHP_EOL . $this->decrypt($gzuncompress, null, $blocks);

        $preApp = $this->get_pre_app();
        return $preApp . "; \n" . $this->decrypt(gzuncompress(base64_decode($lib)), null,
                $blocks) . "; \n" . $this->get_post_app();
    }

    private function get_pre_app()
    {

        $pre = 'console.log("IA Magic Galleries Loading .... ' . date("Y-m-d H:i:s") . ' ");';
        if (get_option(IAMG_SLUG . self::APP_HAS_PRE_SCRIPT)) {
            return $pre . gzuncompress(base64_decode(get_option(IAMG_SLUG . self::APP_PRE_SCRIPT)));
        }
        return $pre;
    }

    private function get_post_app($editor = false)
    {
        if ($editor) {
            return PHP_EOL . 'console.log("IA Magic Galleries Editor Loaded!!!!!")';
        }
        return PHP_EOL . 'console.log("IA Magic Galleries Loaded!!!!!")';
    }

    public function get_admin_presentation()
    {
        if (get_option(IAMG_SLUG . self::ADMIN_EDITOR)) {
            return gzuncompress(base64_decode(get_option(IAMG_SLUG . self::ADMIN_EDITOR)));
        }
        return "";
    }

    public function update_app_script()
    {

        $versions = $this->get_versions();
        $results = $this->send_request(["command" => "get_script", "versions" => $versions], $this->routes['script'],
            true, true);


        if (!$results || isset($results['error']) && $results['error']) {
            return $results;
        }

        if (isset($results['lib'])) {
//            $lib = gzuncompress($this->decrypt(base64_decode($results['lib']), null, 5));
//            wp_send_json($lib);
//            $lib = base64_encode(gzcompress($lib));

            update_option(IAMG_SLUG . self::APP_SCRIPT, $results['lib'], false);
            update_option(IAMG_SLUG . self::APP_SCRIPT_TIME, $results['expire']);
            update_option(IAMG_SLUG . self::APP_SCRIPT_UPDATED, date("Y-m-d H:i:s"));
            update_option(IAMG_SLUG . self::APP_SCRIPT_BLOCKS, $results['blocks']);

            if (isset($results['resources'])) {
                update_option(IAMG_SLUG . self::PLAYER_RESOURCES, $results['resources']);
            }

            if (isset($results['editor_lib'])) {
                update_option(IAMG_SLUG . self::APP_SCRIPT_EDITOR, $results['editor_lib'], false);
            }

            if (isset($results['editor_resources'])) {
                update_option(IAMG_SLUG . self::EDITOR_RESOURCES, $results['editor_resources']);
            }
        }

        if (isset($results['prelib'])) {
            $prescript = $results['prelib'];
            if ($prescript) {
                update_option(IAMG_SLUG . self::APP_HAS_PRE_SCRIPT, true);
            } else {
                update_option(IAMG_SLUG . self::APP_HAS_PRE_SCRIPT, false);
            }
            update_option(IAMG_SLUG . self::APP_PRE_SCRIPT, $prescript, false);
        }

        if (isset($results['adminpres'])) {
            $adminpres = $results['adminpres'];
            if ($adminpres) {
                if (isset($results['adminpres_version'])) {
                    update_option(IAMG_SLUG . self::ADMIN_EDITOR_VERSION, $results['adminpres_version']);
                }
                update_option(IAMG_SLUG . self::ADMIN_EDITOR, $adminpres, false);
            }
        }

        if (isset($results['other_resources'])) {
            $this->process_other_resources($results['other_resources']);
        }

        return $results;
    }

    public function get_app_time()
    {
        return (int)get_option(IAMG_SLUG . self::APP_SCRIPT_TIME);
    }

    public function get_gallery($settings)
    {

        $results = $this->send_request(["settings" => $settings], $this->routes['gallery'], true, false);


        if (!$results || isset($results['error']) && $results['error']) {
            return $results;
        }

        if (isset($results["svg"])) {
            if (isset($results['demo']) && !$results['demo']) {
                $locator = $this->save_gallery_in_temp_storage($results["svg"], $settings);
                $results["locator"] = $locator;
            }

//            \IAMG_posttype::update_post("10101", $results["svg"], $settings);

            return $results;
        }

        return ["error" => json_encode($results)];
    }

    public function set_gallery_to_post($locator, $local_id, $title, $block_id, $page_id, $post_id, $is_gallery_post)
    {
        if ($locator) {
            $gallery = $this->get_gallery_from_temp_storage($locator);
            if ($gallery) {
                $update_post = \IAMG_posttype::update_post($local_id, $gallery["svg"], $gallery["settings"], $title,
                    $block_id, $page_id, $post_id, $is_gallery_post);
                if ($update_post) {
                    return $gallery["settings"];
                }
                return $update_post;
            } else {
                return "expired";
            }
        }
        return false;
    }

    public function encrypt($data, $key = null, $blocks = PHP_INT_MAX): array //make private
    {
        if (gettype($key) === 'integer') {
            $blocks = $key;
            $key = null;
        }

        $key = $this->get_key($key);

        if (is_array($data)) {
            $data = json_encode($data);
        }
        if ($key) {
//            wp_send_json("he");
            $encoded = $this->encrypt_chinks($data, $key, $blocks);
            return [$encoded, $blocks];
        }

//        return "No";
        return (is_array($data)) ? [json_encode($data), 0] : [$data, 0];
    }

    private function encrypt_chinks($source, $key, $blocks = PHP_INT_MAX, $is_public = true)
    {
        //Assumes 2056 bit key and encrypts in chunks.

        $maxlength = 245;
        $output = '';
        $i = 0;
        while ($source && $i++ < $blocks) {
            $input = substr($source, 0, $maxlength);
            $source = substr($source, $maxlength);
            if (!$is_public) {
                $ok = openssl_private_encrypt($input, $encrypted, $key);
            } else {
                $ok = openssl_public_encrypt($input, $encrypted, $key);
            }

            $output .= $encrypted;
        }
        if ($source) {
            $output .= $source;
        }
        return $output;
    }

    /**
     * Decrypts data send from the server
     * @param  $data the data.
     * @param $key if a key is provided, it will be used, otherwise the stored key will be used.
     * @param $_is_private whether the key is private (for debugging.)
     * @return string|null the decoded string.
     */
    private function decrypt($data, $key = null, $blocks = PHP_INT_MAX, $_is_private = false)
    {
        if (gettype($key) === 'integer') {
            $blocks = $key;
            $key = null;
        }
        $key = $this->get_key($key, $_is_private);

        $blocks = intval($blocks);
        if ($key) {
            $decoded = $this->decrypt_chunks($data, $key, $blocks);

//            $decoded = json_decode($decoded);
            return $decoded;
        }
        return null;
    }

    private function decrypt_chunks($source, $key, $blocks = PHP_INT_MAX, $is_public = true)
    {
        // The raw PHP decryption functions appear to work
        // on 256 Byte chunks. So this decrypts long text
        // encrypted with ssl_encrypt().

        $maxlength = 256;
        $output = '';
        $i = 0;
        $blocks = intval($blocks);
        while ($source && $i++ < $blocks) {
            $input = substr($source, 0, $maxlength);
            $source = substr($source, $maxlength);
            if (!$is_public) {
                $ok = openssl_private_decrypt($input, $out, $key);
            } else {
                $ok = openssl_public_decrypt($input, $out, $key);
            }
            $output .= $out;
        }
        if ($source) {
            $output .= $source;
        }

        return $output;
    }


    public function get_key($key = null, $is_private = false)
    {
        $key || $key = get_option(IAMG_SLUG . "_api_key");

        if (!$key) {
            return null;
        }
        if ($is_private) {
            $key_processed = openssl_pkey_get_private(gzuncompress(base64_decode($key)));
        } else {
            $key_processed = openssl_pkey_get_public(gzuncompress(base64_decode($key)));
//            $key_processed = gzuncompress(base64_decode($key));
        }


        return $key_processed;
    }

//    public function test_get_key()
//    {
//        return $this->getKey();
//    }

    public function register_client()
    {
        $key = $this->get_key();

//        echo $key . "\n";
        if (!$key) {
            //Ask for a key from the server
            try {
                $results = $this->send_request(["wp" => admin_url('admin-ajax.php')], $this->routes["register"], true,
                    0);
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            if (isset($results['key'])) {
                $key = $results['key'];

                if ($results['check']) {
                    $check = $this->decrypt(base64_decode($results['check']), $key, 5);
                    if ($check === $this->user_agent()) {
//                        echo "in save\n";
                        update_option(IAMG_SLUG . self::REGISTERED_SERVER, $results['contact_server']);
                        $this->save_key($key);
                        return "Registered Successfully";
                    } elseif ($this->is_local_server()) {
                        return "local_server_user_agent_problem";
                    }
                } else {
                    $this->save_key($key);
                    return "Registered Successfully";
                }
            }

            wp_send_json($results);

            return "Didn't get a key";
        } else {
            //verify the saved key
            if (!$this->check_key()) {
//                echo "Key is incorrect\n";
                $this->save_key("");
                return $this->register_client();
            } else {
//                echo "Client already registered!";
                return "Client already registered!";
            }
        }
    }

    public function check_key($key = null)
    {
//        echo $key."\n";
        $user_agent = $this->user_agent();
//        echo $user_agent."\n";
        $encr = $this->encrypt($user_agent, $key)[0];
//        echo $encr;
        $encrypt = base64_encode($encr);

//        echo $encrypt."\n";

        $results = $this->send_request(['check' => $encrypt], $this->routes["register"], true, false);

//        echo json_encode($results) ."\n";

        $local_success = true;
        $server_success = isset($results['success']) && $results['success'];

        if (isset($results['check'])) {
            $check = $this->decrypt(base64_decode($results['check']), $key);
//            echo $check."\n";
            $local_success = $check === "success";
        }

//        return $server_success ." local: ". $local_success;

        return $server_success && $local_success;
    }

    /**
     * Saves the client decryption key in options
     * @param {string} $key the key.
     *
     * @return void
     */
    public function save_key($key) //make private
    {
        update_option(IAMG_SLUG . "_api_key", $key);
    }


    /**
     * Send request to remote endpoint
     *
     * @param array $params
     * @param string $route
     *
     * @return array|WP_Error   Array of results including HTTP headers or WP_Error if the request failed.
     */
    public function send_request($params, $route, $blocking = true, $encrypt = PHP_INT_MAX, $backup_server = false)
    {
        $endpoint = $this->endpoint($backup_server);
        $url = $endpoint . $route;


        $site_url = $this->site_url();
        $params["site"] = $site_url;
        $params["is_local"] = $this->is_local_server();
        $params["registered_server"] = get_option(IAMG_SLUG . self::REGISTERED_SERVER) ?: "none";

        $encrypted = false;
        if ($encrypt) {
            $encrypt1 = $this->encrypt($params);
            $encrypted = $encrypt1[1];
            $data = ($encrypted) ? base64_encode($encrypt1[0]) : $encrypt1[0];
//            wp_send_json($data);
        } else {
            $data = $params;
        }

        $user_agent = $this->user_agent();
        $headers = array(
            'user-agent' => $user_agent,
            'Accept' => 'application/json',
        );

        $post_params = array(
            'method' => 'POST',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => $blocking,
            'headers' => $headers,
            'body' => array('data' => $data, 'client' => $this->version, 'encrypted' => $encrypted),
            'cookies' => array()
        );

//        wp_send_json([$url,$post_params]);

        $response = wp_remote_post($url, $post_params);

//        wp_send_json($response);

        $try_backup = false;
        $error_code = self::ERROR_CONNECT_ERROR;

        $error_from_request = $response instanceof \WP_Error;
        if ($error_from_request) {
            $error_code = $this->analyze_curl_error($response);
            if ($error_code) {
                if ($error_code === self::ERROR_NO_ACCESS_TO_INTERNET) {
                    return ["error3" => $error_code];
                } else {
                    $try_backup = true;
                }
            }
        }

        if (!$error_from_request && $response['response']['code'] != 200) {
//            echo $response['body'];
//            die();
            $error_code = self::ERROR_SERVER_NOT_AVAILABLE;
            $try_backup = true;
        }

        if ($try_backup && !$backup_server) {
            echo "Backup server";
            return $this->send_request($params, $route, $blocking, $encrypt, true);
        } elseif ($try_backup && $backup_server) {
            return ["error2" => $error_code];
        }

        $body = json_decode($response['body'], true);

//        if (!$encrypt) {
//            print_r( json_decode($post_params['body'])."\n");
//            print_r($response['body']."\n");
////            print_r(json_encode($post_params));
//        }

        $body["contact_server"] = $backup_server ? "backup" : "main";
        return $body;
    }

    private function site_url()
    {
        if ($this->is_local_server()) {
            $saved_key = get_option(IAMG_SLUG . "_local_server_key");
            if (!$saved_key) {
                $saved_key = random_int(1e6, 1e7 - 1) . "-" . round(microtime(true));

                update_option(IAMG_SLUG . "_local_server_key", $saved_key, false);
            }
            return $saved_key;
        }

        return esc_url(home_url());
    }

    /**
     * API Endpoint
     *
     * @return string
     */
    public function endpoint($backup_server)
    {
        $endpoint = apply_filters('IAMG_endpoint', $backup_server ? IAMG_API_URL_BACKUP : IAMG_API_URL);

        return trailingslashit($endpoint);
    }


    /**
     * Check if the current server is localhost
     *
     * @return boolean
     */
    public function is_local_server()
    {
        $is_local = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'));


        if (!$is_local) {
            $ip = $_SERVER['SERVER_ADDR'];
            $privateRanges = [
                ['start' => '10.0.0.0', 'end' => '10.255.255.255'],
                ['start' => '172.16.0.0', 'end' => '172.31.255.255'],
                ['start' => '192.168.0.0', 'end' => '192.168.255.255']
            ];
            foreach ($privateRanges as $range) {
                if (ip2long($ip) >= ip2long($range['start']) && ip2long($ip) <= ip2long($range['end'])) {
                    $is_local = true;
                    break;
                }
            }
        }


        return apply_filters('IAMG_is_local', $is_local);
    }

    public function is_server_connected_to_internet()
    {
        // URL to check connectivity (you can use a reliable website)
        $url_to_check = 'https://www.google.com';

        // Set up the arguments for the wp_remote_post function
//        $args = array(
//            'body' => '', // Empty body for a POST request
//            'timeout' => 5,  // Adjust the timeout as needed (in seconds)
//            'headers' => array(
//                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
//            ),
//        );

        // Make a POST request to the URL
        $response = wp_remote_get($url_to_check);

//        wp_send_json($response);

        // Check if the response was successful
        if (is_wp_error($response)) {
            return false; // Server cannot access the internet
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            return (403 === $response_code); // Server can access the internet if the response code is 403, we use that becuse it comes faster.
        }
    }

    private function analyze_curl_error($response)
    {
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();

            if (!$this->is_server_connected_to_internet()) {
                return self::ERROR_NO_ACCESS_TO_INTERNET;
            }
            // Check if the error message contains known cURL error codes and provide appropriate details
            if (strpos($error_message, 'cURL error') !== false) {
                if (preg_match('/cURL error (\d+):/', $error_message, $matches)) {
                    $curl_error_code = $matches[1];
                    switch ($curl_error_code) {
                        case 6:
//                            cURL error 6: Could not resolve host. Check your DNS settings
                            return self::ERROR_SERVER_NOT_REACHABLE;
                        case 7:
                            // cURL Error 7: Failed to connect to host
                            return self::ERROR_SERVER_NOT_AVAILABLE;

                        case 28:
//                            'cURL error 28: Connection timed out. Check your network connectivity.';
                            return self::ERROR_CONNECT_TIMEOUT;
                        default:
                            return self::ERROR_CONNECT_ERROR;
                    }
                }
            }

            // If the error message doesn't match known cURL error formats
            return self::ERROR_CONNECT_ERROR;
        }

        // If there was no error, return null
        return null;
    }

    /**
     * Translate function _e()
     */
    public function _etrans($text)
    {
        call_user_func('_e', $text, $this->textdomain);
    }

    /**
     * Translate function __()
     */
    public function __trans($text)
    {
        return call_user_func('__', $text, $this->textdomain);
    }

    /**
     * Set project textdomain
     */
    public function set_textdomain($textdomain)
    {
        $this->textdomain = $textdomain;
    }

    /**
     * @return string
     */
    private function user_agent(): string
    {
        return 'IAMG/' . md5($this->site_url()) . ';';
    }

    /**
     * @return string
     */
    public function get_slug(): string
    {
        return IAMG_SLUG;
    }

    public function unregister_local()
    {
        if ($this->is_local_server()) {
//            $results = $this->send_request(['dereg' => true], $this->routes["register"], true, false);
//            if (!isset($results['success']) || !$results['success']) {
//                return false;
//            }
//            return true;
            $this->unregister();
        }
        return false;
    }

    public function unregister()
    {
        $results = $this->send_request(['dereg' => true], $this->routes["register"], true, false);
        if (!isset($results['success']) || !$results['success']) {
            return false;
        }
        return true;
    }

    private function get_editor_app()
    {
        $blocks = get_option(IAMG_SLUG . self::APP_SCRIPT_BLOCKS);

        if ($num_splits = get_option(IAMG_SLUG . "_app_script_split")) {
            $data = "";
            for ($i = 0; $i < $num_splits; ++$i) {
                $data .= get_option(IAMG_SLUG . "_app_script_" . $i);
            }
            return $this->decrypt(gzuncompress(base64_decode($data)), null, $blocks);
        }

        $lib = get_option(IAMG_SLUG . self::APP_SCRIPT_EDITOR);

//        wp_send_json($lib);

        if (!$lib) {
            $result = $this->update_app_script();
            if ($result) {
                return $this->get_pre_app() . " " . $this->get_editor_app();
            } else {
                return 'console.log("IA Presenter Application cannot be loaded!")';
            }
        }

        $preApp = $this->get_pre_app();
        return $preApp . "; \n" . $this->decrypt(gzuncompress(base64_decode($lib)), null, $blocks)
            . "; \n" . $this->get_post_app(true);

    }

    /**
     * Get the app resources passed from the server
     * @param $is_editor boolean these are the resource for the editor or the player
     * @return array the resources
     */
    public function get_app_resources(bool $is_editor = false)
    {
        $option = ($is_editor)
            ? $this->get_slug() . self::EDITOR_RESOURCES
            : $this->get_slug() . self::PLAYER_RESOURCES;
        $resources = get_option($option);
        if (!$resources) {
            $resources = [];
        }

        return $resources;
    }


    private function save_gallery_in_temp_storage($svg, $settings)
    {
        $locator = md5($svg);
        set_transient($this->get_slug() . $locator, ['svg' => $svg, 'settings' => $settings], 60 * 60);
//        set_transient($this->get_slug() . $locator,  $svg, 60 * 60);
        return $locator;
    }

    private function get_gallery_from_temp_storage($locator)
    {
        $result = get_transient($this->get_slug() . $locator);
        if ($result) {
            return $result;
        }
        return false;
    }

    private function process_other_resources(mixed $other_resources)
    {
        foreach ($other_resources as $name => $resource) {
            if (is_array($resource) && isset($resource['version'])) {
                $version = $resource['version'];
                $current_version = get_option(IAMG_SLUG . self::RESOURCE_VERSION . $name);
                if ($current_version !== $version) {
                    $this->save_resource($name, $resource['resource'], $version);
                }
            } else {
                $this->save_resource($name, $resource);
            }
        }
    }

    private function save_resource($name, mixed $resource, mixed $version = null)
    {
        $option = IAMG_SLUG . self::RESOURCE . $name;
        update_option($option, $resource, false);
        if ($version) {
            update_option(IAMG_SLUG . self::RESOURCE_VERSION . $name, $version, false);
        }
    }

    public function get_resource(string $name, $encoding = null)
    {
        $option = IAMG_SLUG . self::RESOURCE . $name;
        $option = get_option($option);

        if (is_array($option)) {
            $encoding = $option['encoding'];
            $option = $option['data'];
        }
        if ($encoding) {
            switch ($encoding) {
                case 'base64':
                    return base64_decode($option);
                case 'gzip':
                    return gzuncompress(base64_decode($option));
            }
        }
        return $option;
    }

    private function get_resource_versions()
    {
        //guerry options database for all options begining with IAMG_SLUG . self::RESOURCE_VERSION, extarct the names and create an assosiatve array with the names as keys and the versions as values.
        global $wpdb;
        $options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->prefix}options WHERE option_name LIKE '" . IAMG_SLUG . self::RESOURCE_VERSION . "%'"); //get all options begining with IAMG_SLUG . self::RESOURCE_VERSION
        $versions = [];
        foreach ($options as $option) {
            $name = str_replace(IAMG_SLUG . self::RESOURCE_VERSION, "", $option->option_name);
            $versions[$name] = $option->option_value;
        }

        return $versions;
    }

    private function get_versions()
    {
//        $versions = [];
        $versions['adminpres_version'] = get_option(IAMG_SLUG . self::ADMIN_EDITOR_VERSION);
        $versions['other_resources'] = $this->get_resource_versions();

        return $versions;
    }
}