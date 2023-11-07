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

if (!class_exists('IAMagicGalleries/Client')) {
    require_once(IAMG_PATH . '/src/IAMagicGalleries/Client.php');
}

if (!class_exists('IAMagicGalleries/ImageHandler')) {
    require_once(IAMG_PATH . '/src/IAMagicGalleries/ImageHandler.php');
}

add_action('wp_ajax_iamg_test', 'run_test');
add_action('wp_ajax_nopriv_iamg_test', 'run_test');

add_action('wp_ajax_iamg_test_images', 'getImages');
add_action('wp_ajax_nopriv_iamg_test_images', 'getImages');

function run_test()
{
    $client = new Client();

//    wp_send_json("Test");
//    try {
//        $response = $client->unregister_local();
//    } catch (Exception $e) {
//        wp_send_json($e->getMessage());
//    }
//    $time = microtime(true);


//    $client->save_key("");

//    $response = $client->register_client();

    $response =  $client->update_app_script();

//    wp_send_json($response);
//
//    wp_send_json(admin_url( 'admin-ajax.php' ));

//    $response = $client->get_app(true);

//    $response = $client->is_server_connected_to_internet();

//    $key = $client->get_key();

//    $response = $client->check_key();

//    echo microtime(true) - $time;

//    $ih = new ImageHandler();

//    wp_send_json($ih->build_image_index());

//    $response = $ih->get_for_library(0, 50, false, "date(2023-01, today)");

//    $client->process_other_resources([
//        "res1" => [
//            'version' => '1.0.0',
//            'resource' => [
//                'data' => base64_encode("resource 1"),
//                'encoding' => 'base64'
//            ]
//        ],
//        'res2' => [
//            'version' => '1.0.0',
//            'resource' => [
//                "data" => base64_encode("resource 2"),
//                'encoding' => 'base64'
//            ],
//        ],
//        'res3' => [
//            'version' => '1.1.0',
//            'resource' => [
//                'data' => base64_encode(gzcompress("resource 3")),
//                'encoding' => 'gzip'
//            ]
//        ],
//    ]);
//
//    $response = $client->get_resource_versions();
//    $response[] = $client->get_resource('res1');
//    $response[] = $client->get_resource('res2');
//    $response[] = $client->get_resource('res3');

    wp_send_json($response);
}

function getImages()
{
    $imageHandler = new ImageHandler();

    wp_send_json($imageHandler->get_for_library(1, 10));
}