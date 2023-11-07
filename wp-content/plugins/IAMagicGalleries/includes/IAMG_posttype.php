<?php
/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

if (!defined('WPINC')) {
    exit;
}

class IAMG_posttype
{

    private $include_clipboard_script = false;

    public function __construct()
    {
        add_action('init', [$this, 'create_post_type_iamg']);
        add_filter('register_post_type_args', [$this, 'disable_autosave_for_iamg'], 10, 2);
        add_action('plugins_loaded', [$this, 'iamg_main_init']);

        if (is_admin()) {
            add_action('admin_init', [$this, 'iamgRedirectOverview']);

            add_action('admin_enqueue_scripts', [$this, "enque_admin_styles"]);
//            add_action('wp_enqueue_scripts', [$this, 'add_admin_styles']);

            add_filter('manage_edit-' . IAMG_POST_TYPE . '_columns', array($this, 'gallery_custom_columns'));
            add_action('manage_posts_custom_column', array($this, 'gallery_custom_column_content'));

            add_action('admin_footer', array($this, 'include_clipboard_script'));
            add_action('add_meta_boxes', [$this, 'remove_custom_fields_meta_box'], 20);
        }
    }

    function create_post_type_iamg()
    {

//        require_once IAMG_INCLUDES_PATH . 'IAMG_GalleryUpdate.php';

        global $_wp_admin_css_colors;
//        print_r("Background " . $this->admin_color);

        $label = array(
            'name' => 'IA Magic Galleries',
            'singular_name' => __('IAMG', 'IAMG'),
            'all_items' => __('Manage Galleries', 'IAMG'),
            'add_new' => __('Add Gallery', 'IAMG'),
//		'add_new_item'  => __( 'Add Gallery', 			'IAMG' ),
            'edit_item' => __('Edit Gallery Post', 'IAMG'),

            'add_new_item' => __('Add New IA Magic Gallery', 'IAMG'),
            'view_item' => __('View IA Magic Gallery', 'IAMG'),

            'search_items' => __('Search Magic Galleries', 'IAMG'),
            'parent_item_colon' => __('Parent Magic Galleries:', 'IAMG'),
            'not_found' => __('No galleries found.', 'IAMG'),
            'not_found_in_trash' => __('No galleries found in Trash.', 'IAMG'),

            'menu_name' => _x('IA Magic Galleries', 'admin menu', 'IAMG'),
            'name_admin_bar' => _x('IAMG', 'add new on admin bar', 'IAMG'),

        );


        $supportArray = array('title', 'thumbnail');
        if (get_option(IAMG_PREFIX . 'categoryShow', 0)) {
            $supportArray[] = 'page-attributes';
        }


        $args = array(
            'labels' => $label,

            'description' => __('IA Magic Galleries, dynamic galleries for you site', 'IAMG'),

            'rewrite' => false, //array('slug' => 'gallery', 'with_front' => true),
            'public' => true,
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => $supportArray,
            'menu_icon' => path_join(IAMG_URL, 'images/admin/IAMG_icon_16_dark.png'), //'dashicons-format-gallery',
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 100,

            'show_in_rest' => true,

//		'rest_base'          => 'iamg',

            /*'publicly_queryable' => true,
            'show_ui'            => true,

            'query_var'          => true,
            'capability_type'    => 'post',


            'rest_controller_class' => 'WP_REST_Posts_Controller',*/
        );


        register_post_type(IAMG_POST_TYPE, $args);

        if (
            is_admin() &&
            get_option('iamg_after_install', 0) == '1'
        ) {

            add_action('wp_loaded', [$this, 'iamgInstallRefresh']);
        }
    }

    function disable_autosave_for_iamg($args, $post_type)
    {
        if (IAMG_POST_TYPE === $post_type) {
            $args['autosave'] = false;
        }
        return $args;
    }

    function iamgInstallRefresh()
    {

        global $wp_rewrite;
        $wp_rewrite->flush_rules();

        if (delete_option('iamg_after_install')) {
            update_option('iamg_redirect_overview', true);
        }

    }


    function iamgRedirectOverview()
    {
        if (get_option('iamg_redirect_overview', false)) {
            delete_option('iamg_redirect_overview');
            wp_redirect(admin_url('edit.php?post_type=' . IAMG_POST_TYPE . '&page=iamg-overview-page'));
            exit();
        }
    }


    function iamg_include($filesForInclude, $path = '')
    {
        $filesArray = array();
        if (empty($filesForInclude)) {
            return;
        }

        if (!is_array($filesForInclude)) {
            $filesArray[] = $filesForInclude;
        } else {
            $filesArray = $filesForInclude;
        }

        for ($i = 0; $i < count($filesArray); $i++) {
            $item = $filesArray[$i];
            if (file_exists($path . $item)) {
                require_once $path . $item;
            }
        }
    }


//iamg_include('cache.php', IAMG_INCLUDES_PATH);


    function iamg_main_init()
    {

//		if(
//			iamg_gallery_get_current_post_type() == IAMG_TYPE_POST &&
//			( iamg_gallery_is_edit_page('new') || iamg_gallery_is_edit_page('edit') ) &&
//			rbsGalleryUtils::getTypeGallery() != 'slider'
//		){
//
//			// Adding the Metabox class
////			iamg_include('init.php', IAMG_CMB_PATH);
//
//
//			iamg_include('iamg_gallery_edit.php', IAMG_INCLUDES_PATH);
//		}

        /* only backend */
        if (is_admin()) {
//			iamg_include( array(
//				'iamg_gallery_media.php',
//				'iamg_gallery_menu.php',
//				'iamg_gallery_settings.php'
//			), IAMG_INCLUDES_PATH);
        }

        /* Frontend*/
//		iamg_include(array( 'iamg_gallery_class.php', 'iamg_gallery_frontend.php' ), IAMG_FRONTEND_PATH);

        /* AJAX */
//		iamg_include('iamg_gallery_ajax.php', IAMG_INCLUDES_PATH);
//		iamg_include('iamg_create_post_ajax.php', IAMG_EXTENSIONS_PATH);

        /*  Init function */

        /* backup init */
//		if( get_option( IAMG_OPTIONS.'addon_backup', 0 )  ){
//			iamg_include('backup/backup.init.php', 		IAMG_EXTENSIONS_PATH);
//		}
        /* category init */
//		if(
//			!get_option(IAMG_PREFIX.'categoryShow', 0) &&
//			!( isset($_GET['page']) && $_GET['page'] != 'iamg-cat' )
//		){
//			iamg_include('category/category.init.php', 	IAMG_EXTENSIONS_PATH);
//		}

        // check for v3
        //iamg_include('categoryPage/category.init.php', 	IAMG_EXTENSIONS_PATH);


        /* stats init */
//		if( get_option( IAMG_OPTIONS.'addon_stats', 0 )  ){
//			iamg_include('stats/stats.init.php', 	IAMG_EXTENSIONS_PATH);
//		}
    }

    //Static functions

    public static function add_post(
        $title,
        $content,
        $pres_parameters,
        $id_local = null,
        $block_id = null,
        $page_id = null,
        $post_id = null,
        $is_gallery_post = false
    ) {
        $url = ($id_local) ? admin_url('admin-ajax.php') . '?action=iamg_pres&id=' . $id_local : "";

        $post_def = [
            'post_title' => $title,
            'post_type' => IAMG_POST_TYPE,
            'post_content' => '<div class="IA_Presenter_Container" presentation="' . $url . '"><\div>',
        ];

        $existing_post = null;
        if ($is_gallery_post && $post_id) {
            $existing_post = get_post($post_id);
            if ($existing_post) {
                if ($title && $existing_post->post_title !== $title) {
                    $post_def['post_title'] = $title;
                } else {
                    $post_def['post_title'] = $existing_post->post_title;
                }

                if ($existing_post->post_status === 'auto-draft') {
                    $post_def['post_status'] = 'draft';
                }

                $id = $existing_post->ID;
                $post_def['id'] = $id;

                wp_update_post($post_def);
            }
        }

        if (!$existing_post) {
            $id = wp_insert_post($post_def);
            wp_update_post([
                'id' => $id,
            ]);
        }

        if ($id_local) {
            add_post_meta($id, "id_local", $id_local, true);
        }
        add_post_meta($id, "presentation", $content, true);
        add_post_meta($id, "presentation_parameters", $pres_parameters, true);
        $pages = [];
        if ($page_id) {
            $pages[$page_id] = ($block_id) ? [$block_id] : [];
        }
        add_post_meta($id, "pages", $pages, true);

        self::get_gallery_count(true);

        return $id;
    }

    public
    static function get_gallery_count(
        $increment = false
    ) {
        $i = get_option(IAMG_SLUG . "_gallery_count");
        if ($i === null) {
            add_option(IAMG_SLUG . "_gallery_count", ($increment) ? 0 : 1, null, false);
            $i = 0;
        } else {
            if ($increment) {
                update_option(IAMG_SLUG . "_gallery_count", $i + 1,);
            }
        }
        return $i;
    }

    public
    static function update_post(
        $id_local,
        $content,
        $pres_parameters,
        $title = null,
        $block_id = null,
        $page_id = null,
        $post_id = null,
        $is_gallery_post = false
    ) {
        $post = self::get_post($id_local);

        if (!$post) {
            if (!$title) {
                $i = self::get_gallery_count();
                $title = "IAMG_Presentation_" . ((string)($i + 1));
            }

            $id = self::add_post($title, $content, $pres_parameters, $id_local, $block_id, $page_id, $post_id,
                $is_gallery_post);
        } else {
            $id = $post->ID;
        }


        $old = get_post_meta($id, "presentation", true);
        $old_params = get_post_meta($id, "presentation_parameters", true);

        // set block_id in array of pages
        if ($page_id) {
            $pages = get_post_meta($id, "pages", true);

            if (!isset($pages[$page_id])) {
                $pages[$page_id] = [];
            }
            if (!in_array($block_id, $pages[$page_id])) {
                $pages[$page_id][] = $block_id;
            }
            update_post_meta($id, "pages", $pages);
        }

        //set post_id in array of posts
//        if ($post_id) {
//            $posts = get_post_meta($id, "posts", true);
//            if (!in_array($post_id, $posts)) {
//                $posts[] = $post_id;
//                update_post_meta($id, "posts", $posts);
//            }
//        }

        if ($old === $content) {
            return true;
        }

        $result = update_post_meta($id, "presentation", $content);

        if ($result) {
            update_post_meta($id, "presentation_parameters", $pres_parameters, true);
        }
        if ($old && $result) {
            add_post_meta($id, "presentation_history", [$old, $old_params]);
        }
        return $result;
    }

    public
    static function get_post(
        $id_local
    ) {
        global $wpdb;
        $sql = "SELECT post_id FROM 
                       {$wpdb->postmeta}
                WHERE meta_key = 'id_local' AND meta_value =%s ;";

        $sql = $wpdb->prepare($sql, $id_local);

//        echo $sql;

        $result = $wpdb->get_results($sql);

//        $query = [
//            'meta_query' =>
//                [
//                    'key' => 'id_local',
//                    'value' => (string)$id_local
//                ],
//            'post_type' => IAMG_POST_TYPE,
//
//        ];
//        $posts = new WP_Query($query);
//
//        $posts = $posts->get_posts();

//        wp_send_json($result[0]);
        if (isset($result[0]) && isset($result[0]) && isset($result[0]->post_id)) {
            $id_local = $result[0]->post_id;
        }

        //if we don't find a post by local_id, assume that post id was passed, and return the post of IAMG type
        $post = get_post($id_local);

//        wp_send_json($post);
        if ($post && $post->post_type === IAMG_POST_TYPE) {
            return $post;
        }

        return null;
    }

    public
    static function get_post_presentation(
        $local_id,
        $decode = true
    ) {
        global $wpdb;
        $sql = "SELECT meta_value FROM 
                       {$wpdb->postmeta} AS pm JOIN 
                           (SELECT post_id FROM {$wpdb->postmeta} 
                                WHERE meta_key='id_local' AND meta_value=%s) AS a 
                               ON pm.post_id = a.post_id 
                WHERE meta_key = 'presentation';";
        $sql = $wpdb->prepare($sql, $local_id);

//        echo $sql;

        $result = $wpdb->get_results($sql);

        if (isset($result[0]) && isset($result[0])) {
            return ($decode) ? base64_decode($result[0]->meta_value)
                : $result[0]->meta_value;
        } else {
            //let's try to get it in a slower with the help of wp, in case something changed in the table architecture
            $post = self::get_post($local_id);
            $content = ($post) ? get_post_meta($post->ID, "presentation", true) : null;
            if ($content) {
                return ($decode) ? base64_decode($content) : $content;
            }
        }
        return null;
    }

    public
    static function clear_post_history(
        $id
    ) {
        delete_post_meta($id, "presentation_history");
    }

    public
    static function render_post(
        $pres_base64 = null,
        $behaviour = "fixed",
        $parameters = null,
        $style_str = ''
    ) {
        if (!$pres_base64) {
            $pres_base64 = get_post_meta(get_the_ID(), "presentation", true);
        }

        if (!$pres_base64) {
            return "";
        }

        $attr_str = "";
        if ($parameters) {
            if (isset($parameters['style'])) {
                $attr_str = 'style="' . self::to_style_string($parameters['style']) . '" ';
            }
            if (isset($parameters['width'])) {
                $attr_str = 'data-width="' . $parameters['width'] . '" ';
            }

            if (isset($parameters['height'])) {
                $attr_str = 'data-height="' . $parameters['height'] . '" ';
            }
            if (isset($parameters['resize-time'])) {
                $attr_str = 'resize-time="' . $parameters['resize-time'] . '" ';
            }
        }

        if ($style_str) {
            $attr_str .= ' style="' . $style_str . '" ';
        }

//        print_r("<br>" . $attr_str . "<br>");
//        $attr_str .= ' style="height:80vh;width:400px;margin-left:calc(50% - 200px)"';
//        $attr_str .= ' data-parent-attributes="data-align:full"';

        $div = '<div class="IA_Presenter_Container" behaviour="' . $behaviour . '" presentation="base64:' . $pres_base64 . '"'
            . $attr_str .
            '></div>';

        return $div;

    }

    private
    static function to_style_string(
        $style = null
    ) {
        if (!$style) {
            return "";
        }
        $ret = "";
        foreach ($style as $style_name => $val) {
            $ret .= $style_name . ":" . $val . ";";
        }
        return $ret;
    }

    public
    function gallery_custom_columns(
        $columns
    ) {
        //todo: add tumbnail support

        $var = array_slice($columns, 0, 1, true) +
//            ['icon' => ''] +
            array_slice($columns, 1, null, true) + [
                IAMG_POST_TYPE . '_media_count' => __('Media', 'iamg'),
                IAMG_POST_TYPE . '_shortcode' => __('Shortcode', 'iamg')
            ];
        return $var;
//        return array_slice( $columns, 0, 1, true ) +
//            array( 'icon' => '' ) +
//            array_slice( $columns, 1, null, true ) +
//            array(
//                FOOGALLERY_CPT_GALLERY . '_template' => __( 'Template', 'foogallery' ),
//                FOOGALLERY_CPT_GALLERY . '_count' => __( 'Media', 'foogallery' ),
//                FOOGALLERY_CPT_GALLERY . '_shortcode' => __( 'Shortcode', 'foogallery' ),
//                FOOGALLERY_CPT_GALLERY . '_usage' => __( 'Usage', 'foogallery' ),
//            );
    }

    public
    function gallery_custom_column_content(
        $column
    ) {
        global $post;

        switch ($column) {

            case IAMG_POST_TYPE . '_media_count':
//                break;

                $params = get_post_meta($post->ID, "presentation_parameters", true);

                if (!isset($params['images'])) {
                    echo __("Empty Gallery");
                    break;
                }

                $num_images = count($params['images']);
                echo (string)$num_images . " " . "images" . __($num_images === 1 ? '' : 's', 'iamg');
                break;

            case IAMG_POST_TYPE . '_shortcode':
//                break;
                $local_id = get_post_meta($post->ID, 'id_local', true);

                if (!$local_id) {
                    echo __("No shortcode: Gallery is not defined");
                    break;
                }

                $shortcode = '[ia_magic_gallery id="' . $local_id . '"]';

                echo '<input type="text" readonly="readonly" style="border:none;" size="' .
                    strlen($shortcode)
//                    "35%"
                    . '" value="' . esc_attr($shortcode) . '" class="iamg-shortcode" />';

                $this->include_clipboard_script = true;

                break;
            case 'icon':
//                $gallery = $this->get_local_gallery( $post );
//                $html_img = foogallery_find_featured_attachment_thumbnail_html( $gallery, array(
//                    'width' => 60,
//                    'height' => 60,
//                    'force_use_original_thumb' => true
//                ) );
//                if ( $html_img ) {
//                    echo $html_img;
//                }
                break;

        }
    }


    private
    function add_admin_styles()
    {
        $screen = get_current_screen();
//        echo "Screen-id " . $screen->id;
        if ($screen->id === 'edit-iamg') {
            $custom_inline_styles = "
                #iamg_media_count{
                    width: 8%;
                }
                #iamg_shortcode{
                    width: 40%;
                }
              ";
            wp_add_inline_style(IAMG_POST_TYPE . '_base-styles', $custom_inline_styles);
        }

    }

    /**
     *
     */
    public
    function enque_admin_styles()
    {
        wp_enqueue_style(IAMG_POST_TYPE . '_base-styles', IAMG_URL . 'css/iamg-base.css');
        $this->add_admin_styles();
    }

    public
    function include_clipboard_script()
    {
        if ($this->include_clipboard_script) { ?>
            <script>
                jQuery(function ($) {
                    $('.iamg-shortcode').on('click', function () {
                        try {
                            //select the contents
                            this.select();
                            //copy the selection
                            document.execCommand('copy');
                            //show the copied message
                            $('.iamg-shortcode-message').remove();
                            $(this).after('<p class="iamg-shortcode-message"><?php _e('Shortcode copied to clipboard!',
                                'iamg'); ?></p>');
                        } catch (err) {
                            console.log('Oops, unable to copy!');
                        }
                    });
                });
            </script>
            <?php
        }
    }

    public
    function remove_custom_fields_meta_box()
    {
        // Replace 'your_custom_post_type' with the name of your custom post type.
        remove_meta_box('postcustom', IAMG_POST_TYPE, 'normal');

    }
}

new IAMG_posttype();





