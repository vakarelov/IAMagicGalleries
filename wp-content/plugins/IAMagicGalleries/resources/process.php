<?php
/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/**
 * The purpose of this file is to provide a way for IA Presenter to obtain dynamically loaded resources.
 * The resources are expected to be in a fixed directory.
 * Some resources are available as files but other resources are available as dynamically generated data by WordPress.
 */

//This part works without WordPress to provide fast access to files in the resources directory directly.
$target = (isset($_GET["target"])) ? $_GET['target'] : null;

if (!$target) {
    http_response_code(404);
    die();
}

function _validate_file_name($target) {
    //prevent backward directory traversal
    $target = str_replace("..", "", $target);

    $filename = realpath(__DIR__ . "/" . $target);
    if ($filename && file_exists($filename)) {
        return $filename;
    }

    return false;
}

$filename = _validate_file_name($target);
if ($filename) {
    $mime_type = mime_content_type($filename);
    header('Content-Type: ' . $mime_type);
    //set catching for one week
    header('Cache-Control: max-age=604800');

    //Here we must access the file direly because we are not in the WordPress environment.
    readfile($filename);
    exit();
};


//This part works with WordPress to provide dynamically generated data.
$get_resource = [
    "image_albums" => ['IAMagicGalleries\IAMG_ImageHandler', 'get_album_names'],
    "test" => [],
];


if (isset($get_resource[$target])) {

    //load WordPress
    require_once realpath(__DIR__ . '/../../../../wp-load.php');
    require_once __DIR__ . '/../src/autoload.php';

    $inst = $get_resource[$target];



    // Initialize nonce as false
    $nonce = false;

// Check if _iamgnonce is set in $_GET
    if (isset($_GET['_iamgnonce'])) {
        $nonce = wp_verify_nonce(sanitize_text_field( wp_unslash($_GET['_iamgnonce'])), 'iamg_direct');
    }
    // If not set in $_GET, check if _iamgnonce is set in $_COOKIE
    else {
        if (isset($_COOKIE['_iamgnonce'])) {
            $nonce = wp_verify_nonce(sanitize_text_field( wp_unslash($_COOKIE['_iamgnonce'])), 'iamg_direct');
        }
    }

    $admin_restriction = isset($inst[2]) && $inst[2] && !is_admin();

    if (!$nonce || $admin_restriction) {
        http_response_code(404);
        die();
    }


    if (method_exists($inst[0], $inst[1])) {
        try {
            header( 'X-Robots-Tag: noindex' );
            send_nosniff_header();
            nocache_headers();
            $result = call_user_func([$inst[0], $inst[1]]);
            wp_send_json($result);
        } catch (Exception $e) {
//            var_dump($e);
        }
    }

}

http_response_code(404);
die("Nothing");

