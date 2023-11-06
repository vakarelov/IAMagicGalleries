<?php
/*
 * Copyright © ${YEAR}  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

if (!defined('WPINC')) {
    die;
}
if (!class_exists('IAMagicGalleries/Client')) {
    require_once(IAMG_CLASSES_PATH . 'Client.php');
}

//if (!class_exists('IAMagicGalleries/AppSettings')) {
require_once(IAMG_CLASSES_PATH . 'AppSettingsBuilder.php');

//}

use IAMagicGalleries\AdminNotice;
use IAMagicGalleries\AppSettingsBuilder;
use IAMagicGalleries\Client;

class IAMG_App_Loader
{
    private $scripts_enqueued = false;

    private $client;

    const USE_MINIFIED = !WP_DEBUG; //false;

    function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'load_IAMG_Scripts']);
        add_action('admin_enqueue_scripts', [$this, 'load_IAMG_Scripts_Admin']);
        add_shortcode('ia_magic_gallery', [$this, 'shortcode_render']);
        add_filter('single_template', [$this, 'load_post_template']);

        add_action('after_setup_theme', function () {
            global $_wp_theme_features;
            if (!(current_theme_supports('align-wide'))) {
                add_theme_support('align-wide');
            }
        });

        //create a new action hook
        add_action('iamg_enqueue_script', [$this, 'enqueue_script']);

        $this->script_caching();
    }


    function load_post_template($template)
    {
        global $post;

        if ($post->post_type === IAMG_POST_TYPE) {
            $this->enqueue_script(null);
            $template = IAMG_PATH . "templates/post.php";
        }

        return $template;
    }

    function enqueue_script($post_scripts = null, $width = 680, $height = 600)
    {
        if ($this->scripts_enqueued) {
            return;
        }
        $this->scripts_enqueued = true;

        wp_register_script("IAPresenter_loader", IAMG_URL . $this->script_selector('js/iaPresenter_loader'));

        wp_enqueue_script(
            'IAPresenter_boot',
            IAMG_URL . $this->script_selector('js/boot_iamg'),
            array('jquery', 'IAPresenter_loader'),
            IAMG_VERSION
        );

        $app_settings_handler = new AppSettingsBuilder();

        wp_localize_script('IAPresenter_boot', 'iamg_settings',
            $app_settings_handler->setup_load_json($post_scripts, null, 'linked', false, $width, $height)
        );

        $this->enque_styles();

//        wp_enqueue_style('iamg-base-admin-styles', IAMG_URL . 'css/iamg-base.css');
    }

    function enqueue_parent_style_scrip()
    {
        wp_enqueue_script(
            'IAMG_ParentStyleScrip',
            IAMG_URL . $this->script_selector('js/parent_style_setter'));
    }

    function load_IAMG_Scripts()
    {
        $post_type = strtolower(get_post_type());
        if ($post_type === strtolower(IAMG_POST_TYPE)) {
//            print_r("In load_IAMG_Scripts");
            $extra = [[$this->script_selector('presentation_expander'), 'presentation_expander_loaded']];
            $extra = null;
            return $this->enqueue_script($extra);
        }
    }

    function load_IAMG_Scripts_Admin()
    {
        $screen = get_current_screen();
//        add_action('wp_head', function () use ($post_type){echo "In load_IAMG_Scripts " . $post_type;});


        if ($screen->id === strtolower(IAMG_POST_TYPE)) {
            $this->load_iamg_editor_files();
        }
    }

    function load_iamg_editor_files()
    {
        global $post;

        wp_register_script("IAPresenter_loader", IAMG_URL . $this->script_selector('js/iaPresenter_loader'));

        wp_enqueue_script(
            'IAPresenter_boot',
            IAMG_URL . $this->script_selector('js/boot_iamg_post_admin'),
            array('jquery', 'IAPresenter_loader'),
            IAMG_VERSION
        );

        $initial_graphics = admin_url('admin-ajax.php') . "?action=iamg_builder_pres";
        $app_settings_handler = new AppSettingsBuilder($initial_graphics);

        $iamg_settings = $app_settings_handler->setup_load_json([
//                    [$this->script_selector('presentation_expander'), 'presentation_expander_loaded']
        ],
            $app_settings_handler->get_editor_resources(),
            'linked',
            true
        );
        $local_id = get_post_meta($post->ID, 'id_local', true);
        $params = get_post_meta($post->ID, "presentation_parameters", true);

        $iamg_settings["id"] = $local_id;
        $iamg_settings["gallery_properties"] = $params;
        $iamg_settings["post_id"] = $post->ID;

        wp_localize_script('IAPresenter_boot', 'iamg_settings',
            $iamg_settings
        );

       $this->enque_styles();
    }

    function enqueue_script_for_caching()
    {
        if ($this->scripts_enqueued) {
            return;
        }
        $this->scripts_enqueued = true;

//        print_r("In Caching Code: <br>");

        wp_register_script("IAPresenter_loader", IAMG_URL . $this->script_selector('js/iaPresenter_loader'));

        wp_enqueue_script(
            'IAPresenter_boot',
            IAMG_URL . $this->script_selector('js/boot_iamg_cache'),
            array('jquery', 'IAPresenter_loader'),
            IAMG_VERSION
        );

        wp_localize_script('IAPresenter_boot', 'iamg_settings',
            [
                "settings" => ['pre_scripts' => (new AppSettingsBuilder())->get_app_link()],
                "resources" => IAMG_JS_URL
            ]);

    }

    function shortcode_render($atts)
    {
        $a = shortcode_atts(array(
            'id' => "155", //get demo code
            'behavior' => 'fixed',
            'height' => null,
            'height_type' => 'pixel',
            'width' => null,
            'width_type' => 'pixel',
            'resize_time' => null,
            'background_color' => "#FFFFFF",
            'background_opacity' => 0
        ), $atts);

//        print_r("In Short Code: " . json_encode((array)$atts) . " " . json_encode($a) . "<br>");

//        $post = get_post($a['id']);

        $id = $a['id'];
        $behavior = $a['behavior'];
//        print_r($behavior);
        $additional_scripts = [];
        $additional_attributes = [];
        $style_css = '';
        $block_class = "block-" . $id;

        $opacity = $a['background_opacity'];
        if (is_numeric($opacity) && $opacity > 0) {
            if ($opacity > 1) {
                $opacity /= 100;
            }
            $color = $a["background_color"];
            if ($this->is_valid_hex_color($color)) {
                $style_css = '<style> .' . $block_class . ' .IA_Designer_Panel_Background{fill:' . $color . '; opacity: ' . $opacity . ';}</style>';
            }
        }

//        print_r("Style" . $style_css);

        switch ($behavior) {
            case 'full':
                $additional_scripts[] = [$this->script_selector('presentation_full'), 'presentation_full_loaded'];
                break;
            case 'fixed':
                $additional_scripts[] = [$this->script_selector('iamg_helper')];
//                $additional_scripts[] = [
//                    $this->script_selector('presentation_expander'),
//                    'presentation_expander_loaded'
//                ];
//                print_r($behavior);

                $this->enqueue_parent_style_scrip();
                $style = [];
                $style['position'] = 'relative';
                if ($a['width']) {
                    $w = $a['width'];
                    if ($a['width_type'] !== 'pixel') {
//                    width:400px;margin - left:calc(50 % -200px)
                        $style["width"] = $w . "vw";
                        $style["margin-left"] = "calc(50% - " . ($w / 2) . "vw)";
                    } else {
                        $style["width"] = $w . "px";
                        $style["margin-left"] = "calc(50% - " . ($w / 2) . "px)";
                    }

                }

                if ($a['height']) {
                    $h = $a['height'];
                    if ($a['height_type'] !== 'pixel') {
                        $style["height"] = $h . "vh";
                    } else {
                        $style["height"] = $h . "px";
                    }
                }

                if ($style) {
                    $additional_attributes['style'] = $style;
                }

                break;
            case 'adaptive':
                $additional_scripts[] = [
                    $this->script_selector('presentation_expander'),
                    'presentation_expander_loaded'
                ];
                $this->enqueue_parent_style_scrip();

                if ($a['width']) {
                    $w = $a['width'];
                    if ($a['width_type'] !== 'pixel') {
//                    width:400px;margin - left:calc(50 % -200px)
                        $additional_attributes['width'] = $w . "%";
                    } else {
                        $additional_attributes['width'] = $w;
                    }
                }

                $style = [];
                $style['position'] = 'relative';

                if ($a['height']) {
                    $h = $a['height'];
                    if ($a['height_type'] !== 'pixel') {
                        $style["height"] = $h . "vh";
                    } else {
                        $style["height"] = $h . "px";
                    }
                }
                if ($style) {
                    $additional_attributes['style'] = $style;
                }

                if ($a['resize_time']) {
                    $additional_attributes['resize-time'] = $a['resize_time'];
                }

                break;
        }

//        print_r(json_encode($additional_attributes) . "<br>");

        $pres = IAMG_posttype::get_post_presentation($id, false);
        $content = IAMG_posttype::render_post($pres, $behavior, $additional_attributes);

        $result = null;

        if ($content) {
            $result = $content;
        } else {
            $post = IAMG_posttype::get_post($id);
            if ($post && $post->post_type === IAMG_POST_TYPE) {
                $result = $post->post_content;
            }
        }
        if ($result) {
            $this->enqueue_script($additional_scripts, null, null);

            $needle = 'class="';
            $pos = strpos($result, $needle);
            if ($pos !== false) {
                $result = substr_replace($result, $needle . $block_class . " ", $pos, strlen($needle));
            }

            return $style_css . $result;
        }
//        print_r("No content for post" . json_encode((array)$atts) . "<br>");
//        print_r("No content for post " . $id . " " . json_encode((array)$pres) . "<br>");
        return null;
    }

    private function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client();
        }
        return $this->client;
    }

    /**
     * @return void
     */
    private function script_caching(): void
    {
        $time_script = $this->getClient()->get_app_time();
        if (!isset($_COOKIE["iamg_lib_loaded"]) || (int)$_COOKIE["iamg_lib_loaded"] < $time_script) {
            setcookie("iamg_lib_loaded", $time_script);
            add_action('wp_footer', [$this, 'enqueue_script_for_caching']);
        }
    }

    private function script_selector($name)
    {
        if (self::USE_MINIFIED) {
            return $name . ".min.js";
        } else {
            return $name . ".js";
        }
    }

    private function is_valid_hex_color($color)
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color);
    }

    /**
     * @return void
     */
    private function enque_styles(): void
    {
        if (WP_DEBUG) {
            wp_enqueue_style(
                'iamg-def-styles',
                IAMG_URL . 'css/ia_designer_general.css'
            );
            wp_enqueue_style(
                'iamg-def-styles',
                IAMG_URL . 'css/ia_presenter_general.css'
            );
        } else{
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
//            IAMG_URL . 'css/iamg-base.css'
        );
    }
}

new IAMG_App_Loader();


