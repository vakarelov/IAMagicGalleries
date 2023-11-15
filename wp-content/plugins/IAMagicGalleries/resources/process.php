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

//This part works without WordPress to provide fast access to files in the resources directly.
$target = (isset($_GET["target"])) ? $_GET['target'] : null;

if (!$target) {
    http_response_code(404);
    die();
}

//prevent backward directory traversal
$target = str_replace("..", "", $target);

$filename = realpath(__DIR__ . "/" . $target);
if (file_exists($filename)) {
    $mime_type = mime_content_type($filename);
    header('Content-Type: ' . $mime_type);

    //Here we must access the file direly because we are not in WordPress environment.
   readfile($filename);
   exit();
};


//This part works with WordPress to provide dynamically generated data.
$get_resource = [
    "image_albums" => ['IAMagicGalleries\ImageHandler', 'get_album_names'],
    "test" => [],
];

if (isset($get_resource[$target])) {
    require_once __DIR__ . '/../src/autoload.php';

    //load WordPress
    require_once __DIR__ . '/../../../../wp-load.php';
    $inst = $get_resource[$target];

    if (isset($inst[2]) && $inst[2] && !is_admin()) {
        http_response_code(404);
        die();
    }

    if (method_exists($inst[0], $inst[1])) {
        try {
            $result = call_user_func([$inst[0], $inst[1]]);
            wp_send_json($result);
        } catch (Exception $e) {
            var_dump($e);
        }
    }

}

http_response_code(404);
die("Nothing");

