<?php
/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

$target = (isset($_GET["target"])) ? $_GET['target'] : null;

if (!$target) {
    http_response_code(404);
    die();
}

$target = str_replace("..", "", $target);

$filename = realpath(__DIR__ . "/" . $target);
if (file_exists($filename)) {
    $mime_type = mime_content_type($filename);
    header('Content-Type: ' . $mime_type);
    readfile($filename);
    exit();
};

$get_resource = [
    "image_albums" => ['IAMagicGalleries\ImageHandler', 'get_album_names'],
    "test" => [],
];

if (isset($get_resource[$target])) {
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

