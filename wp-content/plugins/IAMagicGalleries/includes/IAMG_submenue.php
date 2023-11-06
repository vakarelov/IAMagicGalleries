<?php
/*
 * Copyright © 2023  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/*
 * Copyright © 2023  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/*
 *
 *  * Copyright (c) 2021. Orlin Vakarelov
 *
 */

/**
 * Sub menu class
 *
 * @author Mostafa <mostafa.soufi@hotmail.com>
 */
class IAMG_Submenue
{

    private $parent_slig = "edit.php?post_type=" . IAMG_POST_TYPE;

    /**
     * Autoload method
     * @return void
     */
    public function __construct()
    {
        add_action('admin_menu', [&$this, 'register_sub_menu']);
        add_action('add_meta_boxes', [&$this, 'add_editor_metabox']);

        add_action('post_submitbox_misc_actions', [$this, 'add_saving_info_in_metabox']);
    }

    /**
     * Register submenu
     * @return void
     */
    public function register_sub_menu()
    {
        add_submenu_page(
            $this->parent_slig,
            'IA Magic Gallery Overview',
            'Overview',
            'manage_options',
            'iamg-overview-page',
            [$this, 'overview_page_callback']
        );
        add_submenu_page(
            $this->parent_slig, 'IA Magic Gallery Help', 'Help', 'manage_options', 'iamg-help-page',
            [$this, 'help_page_callback']
        );
    }

    /**
     * Render submenu
     * @return void
     */
    public function overview_page_callback()
    {
        do_action('iamg_enqueue_script');
        $client = new \IAMagicGalleries\Client();
        $pres = $client->get_resource("overview_presentation");
        if (!$pres) {
            $render_post = "<div>Overview Presentation has not been loaded properly</div>";
        } else {
            $render_post = IAMG_posttype::render_post($pres, 'fixed', null, "height:90vh;");
        }

        ?>
        <div class="wrap">
            <?php
            echo $render_post;
            ?>
        </div>'
        <?php
    }

    /**
     * Render submenu
     * @return void
     */
    public function help_page_callback()
    {
        do_action('iamg_enqueue_script');
        $client = new \IAMagicGalleries\Client();
        $pres = $client->get_resource("help_presentation");
        if (!$pres) {
            $render_post = "<div>Help Presentation has not been loaded properly</div>";
        } else {
            $render_post = IAMG_posttype::render_post($pres, 'fixed', null, "height:90vh;");
        }
        ?>
        <div class="wrap">
            <?php
            echo $render_post;
            ?>
        </div>
        <?php
    }

    function create_editor_environment($post)
    {
        $client = new \IAMagicGalleries\Client();
        $pres = $client->get_admin_presentation();
        echo IAMG_posttype::render_post(base64_encode($pres), 'fixed', null, "height:90vh;");
        ?>
        <img class="iamg-loading-gif" style="display: block;
    margin-left: auto;
    margin-right: auto;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);"
             src="<?php echo IAMG_URL . 'images/admin/loading_dots.gif' ?>">
        </img>
        <?php
    }

    function add_editor_metabox()
    {
        add_meta_box('iamg_editor_metabox', __('Edit Gallery Definition'), array(&$this, "create_editor_environment"),
            IAMG_POST_TYPE, 'normal', 'high');
    }

    function add_saving_info_in_metabox()
    {
        ?>
        <div id="saving_announcement" class="misc-pub-section" style="font-size: large;font-weight: bold">
            <?php echo __("Save Gallery with the SAVE button inside the interface, once it is generated.") ?>
        </div>
        <?php
    }


}

new IAMG_Submenue();