<?php
/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

use IAMagicGalleries\AppSettingsBuilder;

if (!class_exists('IAMagicGalleries/AppSettings')) {
    require_once(IAMG_CLASSES_PATH . 'AppSettingsBuilder.php');
}

if (!class_exists('IAMagicGalleries_Block')) {
    class IAMagicGalleries_Block
    {
//        const USE_MINIFIED = WP_DEBUG ? false : true;
        const USE_MINIFIED = false;

        public function __construct()
        {
            add_action('enqueue_block_editor_assets', [$this, 'load_iamg_block_files']);
            add_action('wp_ajax_iamgtest', [$this, 'texst_handle']);
        }

        function load_iamg_block_files()
        {
            wp_register_script('lz-string', IAMG_URL . $this->script_selector('js/dist/lz-string'));
            wp_register_script("IAPresenter_loader", IAMG_URL . $this->script_selector('js/iaPresenter_loader'));
            wp_enqueue_script(
                'iamg-block-script',
                IAMG_URL . $this->script_selector('js/iamg-block'),
                array('wp-blocks', 'wp-i18n', 'wp-editor', 'jquery', 'lz-string', 'IAPresenter_loader'),
                IAMG_VERSION
            );

            $initial_graphics = admin_url('admin-ajax.php') . "?action=iamg_builder_pres"."&_nonce=" . wp_create_nonce('iamg_block');
            $app_settings_handler = new AppSettingsBuilder($initial_graphics);

            $app_settings = $app_settings_handler->setup_load_json([
//                    [$this->script_selector('presentation_expander'), 'presentation_expander_loaded']
            ],
                $app_settings_handler->get_editor_resources(),
                'linked',
                true
            );

            $version = get_bloginfo('version');
            $app_settings['wp_version'] = $version;
//            $app_settings['_nonce'] = wp_create_nonce('iamg_block');

            wp_localize_script('iamg-block-script', 'iap_loader_settings',
                $app_settings
            );

            if (WP_DEBUG) {
                wp_enqueue_style(
                    'iamg-def-styles',
                    IAMG_URL . 'css/ia_designer_general.css'
                );
                wp_enqueue_style(
                    'iamg-def-styles',
                    IAMG_URL . 'css/ia_presenter_general.css'
                );
            } else {
                wp_enqueue_style(
                    'iamg-def-styles',
                    IAMG_URL . 'css/ia_general.min.css'
                );
            }


            if (is_admin()) {
                wp_enqueue_style(
                    'iamg-admin-styles',
                    IAMG_URL . 'css/ia_presenter_admin.css'
                );
            }
            wp_enqueue_style(
                'video-js',
                IAMG_URL . 'css/video-js.css'
            );

        }

        function add_presentations($content)
        {
            $id = get_the_ID();
            print_r("Post id is:{$id}<br>");
            $meta = get_post_meta($id, "presentations");
            if ($meta) {

            }
        }

        function test_handle()
        {
            //todo: add autentication
            $post_id = (int)$_POST['post_id'];
            $pres_id = $_POST['pres_id'];
            $key = "presentations";
            $old_meta = get_post_meta($post_id, $key);
            $data = "test id " . $pres_id;
            if ($old_meta) {
                $old_meta[0][$pres_id] = $data . "update";
                update_post_meta($post_id, $key, $old_meta[0]);
            } else {
                $old_meta = [$pres_id => $data . "new"];
                add_post_meta($post_id, $key, $old_meta);
            }

//			file_put_contents( __DIR__ . "/log.txt", json_encode( $_POST ) . json_encode( $old_meta ) );
//    return $old_meta;
            wp_send_json_success($old_meta);
//    wp_die("Hello");
        }

        private function script_selector($name)
        {
            if (self::USE_MINIFIED) {
                return $name . ".min.js";
            } else {
                return $name . ".js";
            }
        }
    }
}

new IAMagicGalleries_Block();