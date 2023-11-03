<?php
session_start();
$start = microtime(true);
require_once(__DIR__ . "/../../../wp-load.php");
echo "<br>" . (microtime(true) - $start) . "<br>";

$result = get_current_user();

echo $result . "<br>";

if ($_COOKIE["IAMGid"]) {
    echo htmlspecialchars($_COOKIE["IAMGid"]) . "<br>";
    echo htmlspecialchars(get_transient("id")) . "<br>";
    if ($_COOKIE["IAMGid"] === get_transient("id")) {
        echo "match<br>";
    }
}


$images = getMediaImages();

global $wpdb;

foreach ($images as $name => $discr) {
    echo $name . "<br>";
    foreach ($discr as $dims) {
        echo ".    " . $dims['file_name'] . ": " . $dims['dim'][0] . "x" . $dims['dim'][1] . "<br>";
    }
    $sql = "SELECT post_content, post_excerpt FROM `{$wpdb->prefix}posts` as p JOIN `{$wpdb->prefix}postmeta` as pm ON p.ID = pm.post_id WHERE post_title = %s AND meta_key = '_wp_attached_file'";
    $sql = $wpdb->prepare($sql, $name);
    $results = $wpdb->get_results($sql);
    if ($results) {
        $result = $results[0];
        if ($result->post_content ||  $result->post_excerpt){
            echo $result->post_content . " " . $result->post_excerpt . "<br>";
        }
    }
}


echo "<br>" . (microtime(true) - $start);


function getMediaImages($year = null, $month = null)
{
    $base = wp_upload_dir()["basedir"];

    if (!$year && !$month) {
        $years = array_diff(scandir($base), array('..', '.'));

        $results = [];
        foreach ($years as $y) {
            if (is_numeric($y)) {
                $results[] = getMediaImages($y);
            }
        }

        return call_user_func_array('array_merge', $results);
    }

    if (!$month) {
        $months = array_diff(scandir("$base/$year"), array('..', '.'));

        $results = [];

        foreach ($months as $m) {
            if (is_numeric($m)) {
                $results[] = getMediaImages($year, $m);
            }
        }

        return call_user_func_array('array_merge', $results);
    }

    $images = array_diff(scandir("$base/$year/$month"), array('..', '.'));

    $results = [];

    foreach ($images as $image) {
        $anal = processImageName($image, "$base/$year/$month/$image");
        if ($anal) {
            $name = $anal["name"];
            if (!isset($results[$name])) {
                $results[$name] = [];
            }
            $results[$name][] = $anal;
        }
    }
    return $results;
}

function processImageName($name, $filename)
{
    preg_match('/(.+)-((\d{3,4}x\d{3,4})|scaled)\.([a-zA_Z]+)$/', $name, $matches);

    if ($matches) {
        $front_name = $matches[1];
        $dim = $matches[2];
        $extension = $matches[4];
        if ($dim === "scaled") {
            try {
                $sizes = wp_getimagesize($filename);
                $dim = [$sizes[0], $sizes[1]];
            } catch (Exception $e) {
                echo $e["message"];
                return null;
            }
        } else {
            $dim = explode("x", $dim);
            $dim = [(int)$dim[0], (int)$dim[1]];
        }
        return [
            "name" => $front_name,
            "extension" => $extension,
            "file_name" => $name,
            "local_file" => $filename,
            "dim" => $dim
        ];

    } else {
        preg_match('/(.+)\.([a-zA_Z]+)$/', $name, $matches);
        $front_name = $matches[1];
        $extension = $matches[2];
        try {
            $sizes = wp_getimagesize($filename);
            $dim = [$sizes[0], $sizes[1]];
        } catch (Exception $e) {
            return null;
        }
        return [
            "name" => $front_name,
            "extension" => $extension,
            "file_name" => $name,
            "local_file" => $filename,
            "dim" => $dim
        ];
    }

}



