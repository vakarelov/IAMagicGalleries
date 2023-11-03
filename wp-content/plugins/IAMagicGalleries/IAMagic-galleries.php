<?php

/*
Plugin Name: IA Magic Galleries
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: Information Aesthetics
Author URI: https://iaesth.ca
License: GPL2, V2
*/

if (!defined('WPINC')) {
    die;
}
if (!defined("ABSPATH")) {
    exit;
}

//DEFINITIONS

define("IAMG_VERSION", '1.1.0');
define("IAMG", 1);
define("IAMG_MAIN_FILE", __FILE__);
define("IAMG_PATH", plugin_dir_path(IAMG_MAIN_FILE));
define("IAMG_INCLUDES_PATH", IAMG_PATH . 'includes/');
define("IAMG_CLASSES_PATH", IAMG_PATH . 'src/IAMagicGalleries/');


list($slug, $_) = explode('/', plugin_basename(__FILE__));
define("IAMG_SLUG", (extension_loaded('mbstring')) ? mb_strtolower($slug) : strtolower($slug));

define("IAMG_URL", plugin_dir_url(__FILE__));

define("IAMG_POST_TYPE", 'iamg');

define("IAMG_PREFIX", 'iamg_');


define("IAMG_JS_URL", IAMG_URL . 'js/');

//define("IAMG_API_URL", "https://iaesth.ca/apps/IAMG/com");

define("IAMG_API_URL", "http://sandbox.pri/IADesigner/php-vector-graphic/src/IA_Designer/com/");

define("IAMG_API_URL_BACKUP", "http://sandbox.pri/IADesigner/php-vector-graphic/src/IA_Designer/com/");

//define("IAMG_TEMP_MEM_TIME", 30 * 60);


//for debugging remove
add_filter('allowed_http_origins', 'add_allowed_origins');
function add_allowed_origins($origins)
{
    $origins[] = 'http://sandbox.pri';
    return $origins;
}

//Define posttype and admin menues
require_once IAMG_INCLUDES_PATH . 'IAMG_posttype.php';
if (is_admin()){
    require_once IAMG_INCLUDES_PATH . 'IAMG_submenue.php';
}


require_once IAMG_INCLUDES_PATH . 'IAMG_activation.php';

//Adds crone jobs to check if app library must be updated
require_once IAMG_INCLUDES_PATH . 'IAMG_LibHandler.php';

require_once IAMG_INCLUDES_PATH . 'IAMGComDispacher.php';

//This must be after IAMGComDispacher.php
if (is_admin()) {
    require_once IAMG_INCLUDES_PATH . 'block/IAMagicGalleries_Block.php';
//    require_once IAMG_INCLUDES_PATH . 'Gallery_Gen_Link.php';
    require_once IAMG_INCLUDES_PATH . 'IAMG_admin_notices.php';
}
require_once IAMG_INCLUDES_PATH . 'IAMG_App_Loader.php';


//For testing
require_once IAMG_PATH . '/test/test.php';


//
//function iamg_test_post( $data ) {
//	$data = preg_replace_callback( "/\*IA_Magic_gallery_svg_content_(\d+)\*/", function ( $matches ) {
//		if ( $matches ) {
//			return "We found " . $matches[1];
//		}
//	}, $data );
//
//	return $data;
//}


//add_action('the_content', 'add_presentations');


;
//add_action('content_save_pre', 'iamg_test_post');


/* To make your block "dynamic" uncomment
  the code below and in your JS have your "save"
  method return null
*/

/*
function borderBoxOutput($props) {
  return '<h3 style="border: 5px solid' . $props['color'] . '">' . $props['content'] . '</h3>';
}

register_block_type( 'brad/border-box', array(
  'render_callback' => 'borderBoxOutput',
) );
*/

/* To Save Post Meta from your block uncomment
  the code below and adjust the post type and
  meta name values accordingly. If you want to
  allow multiple values (array) per meta remove
  the 'single' property.
*/

/*
function myBlockMeta() {
  register_meta('post', 'color', array('show_in_rest' => true, 'type' => 'string', 'single' => true));
}

add_action('init', 'myBlockMeta');
*/