<?php
/*
 * Copyright © ${YEAR}  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/*
 *
 *  * Copyright (c) 2021. Orlin Vakarelov
 *
 */

namespace IAMagicGalleries;

class ImageHandler
{

    /**
     * @var array
     */
    public $images = [];
    private $videos = [];
    private $slug;

    function __construct($video = false)
    {
        $basename = plugin_basename(__FILE__);
        list($this->slug, $_) = explode('/', $basename);
        if ($video) {
            $this->videos = $this->get_videos();

        }

        if (!$video || $video === "all") {
            $this->images = $this->get_images();
        }
    }

    private function process_date($date, $format = "Y-m-d H:i:s")
    {
        if (is_numeric($date) && !is_string($date)) {
            $date = date($format, floor($date));
            return $date;
        }
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        return date($format, $timestamp);
    }

    public function get_images($date = null, $end_date = null)
    {
        $query_images_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
        );

        if ($date) {
            $date = $this->process_date($date);
            if ($date) {
                if (!$end_date) {
                    $end_date = $this->process_date($date . " +24 hours");
                }
                $query_images_args['date_query'] = [
                    [
                        'after' => $date, // Start date
                        'before' => $end_date, // End date
                        'inclusive' => true, // Include posts from the start and end dates
                    ],
                ];
            }
        }

        $query_images = new \WP_Query($query_images_args);

        $images = array();
        foreach ($query_images->posts as $image) {
            $id = $image->ID;
            $meta = wp_get_attachment_metadata($id);
            $url = wp_get_attachment_url($id);

            $base_url = implode("/", array_slice(explode("/", $url), 0, -1));

            $imageSizes = $this->get_image_sizes($meta, $base_url);
            $images[] = [
                "id" => $id,
                "url" => $url,
                "title" => $image->post_title,
                "thumbnail" => $imageSizes['thumbnail']['url'],
                "caption" => $image->post_excerpt,
                "description" => $image->post_content,
                "alt" => get_post_meta($id, '_wp_attachment_image_alt', true),
                "sizes" => $imageSizes,
                "date" => $image->post_date
            ];
        }

        usort($images, function ($a, $b) {
            return (int)$a["id"] - (int)$b['id'];
        });

//        $date && wp_send_json([$date, $end_date, $images]);

        return $images;
    }

    private function get_videos()
    {
        $query_images_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'video',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
        );


        $query_images = new \WP_Query($query_images_args);

        $images = array();
        foreach ($query_images->posts as $video) {
            $id = $video->ID;
            $meta = wp_get_attachment_metadata($id);
            $url = wp_get_attachment_url($id);

            $base_url = implode("/", array_slice(explode("/", $url), 0, -1));

            $thumbnail = $this->get_video_thumbnail($meta, $base_url);
            $images[] = [
                "id" => $id,
                "url" => $url,
                "title" => $video->post_title,
                "thumbnail" => $thumbnail,
                "caption" => $video->post_excerpt,
                "description" => $video->post_content,
                "alt" => get_post_meta($id, '_wp_attachment_image_alt', true),
                "width" => $meta["width"],
                "height" => $meta["height"]
            ];
        }

        usort($images, function ($a, $b) {
            return (int)$a["id"] - (int)$b['id'];
        });

        return $images;
    }

    private function get_image_sizes($meta, $url_base, $sizes = ['medium', 'large', 'thumbnail'])
    {
        $images = [];
        $expl = explode('/', $meta["file"]);
        $images['full'] = [
            "url" => $url_base . "/" . array_pop($expl),
            "width" => $meta["width"],
            "height" => $meta["height"]
        ];

        foreach ($sizes as $size) {
            if (isset($meta['sizes'][$size])) {
                $im_size_data = $meta['sizes'][$size];
                $images[$size] = [
                    "url" => $url_base . "/" . $im_size_data['file'],
                    "width" => $im_size_data["width"],
                    "height" => $im_size_data["height"]
                ];
            }
        }

        if (isset($meta['original_image'])) {
            $images['original'] = [$url_base . "/" . $meta['original_image']];
        }

        return $images;
    }

    private function get_images_from_album($album)
    {
        if (is_array($album)) {
            $images = [];
            foreach ($album as $alb) {
                $images = array_merge($images, $this->get_images_from_album($alb));
            }
            return $images;
        }

        $last_image_update = get_option($this->slug . "_last_image_update");
        $last_index_time = get_option($this->slug . "_last_image_index");

        if (!$last_image_update || !$last_index_time || $last_index_time > $last_image_update) {
            $index = $this->build_image_index();
        } else {
            $index = get_option($this->slug . "_image_album_index");
        }


        $album = strtolower($album);

        preg_match('/^date\((.+)\)$/i', $album, $date);

        if ($date) {
            $date = explode(",", $date[1]);
            if ($date) {
                return $this->get_images_from_date($date[0], isset($date[1]) ? $date[1] : null, $index);
            }
        }


        if (isset($index['albums'][$album])) {
            return $index['albums'][$album];
        }

        return [];

    }

    public function build_image_index()
    {
        $regex = '/(?:Albums|Tags|Album|Tag):\s*([\p{L}0-9,;@-_\s]+)\s*(?:\.|$)/iu';
        $index = [
            "dates" => [],
            "albums" => [],
        ];

        foreach ($this->images as $image) {
            $date = explode(" ", $this->process_date($image['date'], 'Y m d'));
            $index["dates"][$date[0]][$date[1]][$date[2]][] = $image;
            $description = $image['description'];
            if (!$description) {
                continue;
            }

            $matches = [];
            preg_match_all($regex, $description, $matches);
            if ($matches[1]) {
                foreach ($matches[1] as $match) {
                    $albums = $match;
                    if (isset($albums)) {
                        $albums = explode(",", str_replace(";", ",", $albums));
                        if ($albums) {
                            foreach ($albums as $album_name) {
                                $album_name = strtolower(trim($album_name));
                                $index["albums"][$album_name][] = $image;
                            }
                        }
                    }
                }
            }
        }

        if ($index) {
            if (get_option($this->slug . "_last_image_index")) {
                update_option($this->slug . "_last_image_index", microtime(true));
                update_option($this->slug . "_image_album_index", $index);
            } else {
                add_option($this->slug . "_last_image_index", microtime(true), '', false);
                add_option($this->slug . "_image_album_index", $index, '', false);
            }
        }

        return $index;
    }

    public static function get_album_names()
    {
        list($slug, $_) = explode('/', plugin_basename(__FILE__));
        $last_image_update = get_option($slug . "_last_image_update");
        $last_index_time = get_option($slug . "_last_image_index");

        if (!$last_image_update || !$last_index_time || $last_index_time > $last_image_update) {
            $index = (new ImageHandler())->build_image_index();
        } else {
            $index = get_option($slug . "_image_album_index");
        }

        $albums = ['All'];
        if (isset($index['albums']) && $index['albums']) {
            $album_names = array_keys($index['albums']);
            sort($album_names);
            $album_names = array_map(function ($name) {
                return ucwords($name);
            }, $album_names);
            $albums = array_merge($albums, $album_names);
        }
        return $albums;
    }

    /**(
     * Removes all information from settings["images"] except for thumbnails.
     * @param $settings
     * @return array
     */
    public static function sanitize($settings): array
    {
        if (isset($settings['images'])) {
            $settings["images"] = array_map(function ($image) {
                return [
                    'title' => $image['title'],
                    'thumbnail' => $image['thumbnail']
                ];
            }, $settings['images']);
        }
        return $settings;
    }

    public function get_for_library($start = 0, $number = null, $video = false, $album = "")
    {
        if (!($start)) {
            $start = 0;
        }
        $media = ($video) ? $this->videos : $this->images;
        if ($album && strtolower($album) !== "all") {
            $media = $this->get_images_from_album($album);
        }

        $part = array_slice($media, $start, $number);

//        wp_send_json(["here2", $start, $number,  $part]);

        if ($video) {
            return $part;
        }

        $result = array_map(function ($im) {
            $url = $im['thumbnail'];
            $width = $im['sizes']['thumbnail']['width'];
            $height = $im['sizes']['thumbnail']['height'];
            if (!$url) {
                $url = $im['sizes']['medium']['url'];
            }
            if (!$url) {
                $url = $im['sizes']['large']['url'];
            }
            if (!$url) {
                $url = $im['sizes']['full']['url'];
            }
            if (!$url || strpos($im['url'], $url) !== false) {
                $url = $im['url'];
            }

            $medium = null;
            if ($im['sizes']['medium']['url']) {
                $medium = [
                    $im['sizes']['medium']['url'],
                    $im['sizes']['medium']['width'],
                    $im['sizes']['medium']['height']
                ];
                if (!$width) {
                    $width = $im['sizes']['medium']['width'];
                    $height = $im['sizes']['medium']['height'];
                }
            }
            $lagre = null;
            if ($im['sizes']['large']['url']) {
                $lagre = [
                    $im['sizes']['large']['url'],
                    $im['sizes']['large']['width'],
                    $im['sizes']['large']['height']
                ];
                if (!$width) {
                    $width = $im['sizes']['large']['width'];
                    $height = $im['sizes']['large']['height'];
                }
            }
            $full = null;
            if ($im['sizes']['full']['url']) {
                $full = [
                    $im['sizes']['full']['url'],
                    $im['sizes']['full']['width'],
                    $im['sizes']['full']['height']
                ];
                if (!$width) {
                    $width = $im['sizes']['full']['width'];
                    $height = $im['sizes']['full']['height'];
                }
            }

            if (!$url) {
                return null;
            }

            if ((!$width || !$height) && extension_loaded('gd')) {
                $upload_dir = wp_upload_dir();

                // Check if the URL is within the uploads directory
                if (strpos($url, $upload_dir['baseurl']) === 0) {
                    // Get the relative path from the base URL
                    $relative_path = str_replace($upload_dir['baseurl'], '', $url);

                    // Construct the local file path
                    $local_file_path = $upload_dir['basedir'] . $relative_path;
                    $getimagesize = getimagesize($local_file_path);
                    list($width, $height) = $getimagesize;
                }

            }

            $img_info = [
                "id" => $im['id'],
                "title" => $im['title'],
                "url" => $url,
                "full" => $full,
                "large" => $lagre,
                "medium" => $medium,
                "width" => $width,
                "height" => $height,
            ];

            return $img_info;
        }, $part);

        $result = array_values(array_filter($result));

        return $result;
    }

    private function convert_image_info_for_gallery($info)
    {
        $sizes_info = $info['sizes'];
        foreach (["medium", "large", "full"] as $size) {
            if (isset($sizes_info[$size])) {
                $sizes[] = [
                    $sizes_info[$size]["url"],
                    [$sizes_info[$size]["width"], $sizes_info[$size]["height"]]
                ];
            }
        }
        $result = [
            "sizes" => $sizes,
            "title" => $info['title'],
            "caption" => $info['caption'],
            "description" => $info['description'],
            "alt" => $info['alt'],
            "download" => $sizes_info['original'][0],
            "thumbnail" => $sizes_info['thumbnail']
        ];

        return $result;
    }

    public function get_for_gallery($ids = null)
    {
        $map = [];


        foreach ($this->images as $image) {
            $map[$image['id']] = $image;
        }

        if (!$ids) {
            $ids = array_keys($map);
        }


        $result = [];
        foreach ($ids as $id) {
            if (is_array($id)) {
                $id = $id["id"];
            }
            if (isset($map[$id])) {
                $result[] = $this->convert_image_info_for_gallery($map[$id]);
            }
        }

        return $result;
    }

    private function get_video_thumbnail(array $meta, string $base_url)
    {
        return "";
    }

    private function get_images_from_date(string $start, ?string $end, $index)
    {


        $start_date = $this->process_date($start);
        if ($end) {
            $end_date = $this->process_date($end);
            if (!$start_date || !$end_date) {
                return [];
            }
//            wp_send_json([$start_date, $end_date]);

            if (substr($start_date, -8) !== "00:00:00" || substr($end_date, -8) !== "00:00:00") {
                wp_send_json(["here", $start, $end, $start_date, $end_date]);
                return $this->get_images($start_date, $end_date);
            }


            $explode_s = explode("-", $start);
            $explode_e = explode("-", $end);

            $start_range = array_map(function ($v) {
                return (is_numeric($v) ? (int)$v : false);
            }, $explode_s);
            $end_range = array_map(function ($v) {
                return (is_numeric($v) ? (int)$v : false);
            }, $explode_e,);

//            wp_send_json([$start, $end, $start_range, $end_range]);

            if (in_array(false, $start_range) || in_array(false, $end_range)) {
//                wp_send_json("here2");
                return $this->get_images($start_date, $end_date);
            }
            return $this->get_images_range($start_range, $end_range, $index["dates"]);
        }

        if (!$start_date) {
            return [];
        }

        if (substr($start_date, -8) !== "00:00:00") {
            return $this->get_images($start_date);
        }

        $start_range = array_map(function ($v) {
            return (is_numeric($v) ? (int)$v : false);
        }, explode("-", $start));
        return $this->get_images_range($start_range, $start_range, $index["dates"]);
    }

    private function get_images_range(array $start_range, array $end_range, $index)
    {
        if (!$start_range) {
            $start_range = [0];
        }
        if (!$end_range) {
            $end_range = [100];
        }
        $start = array_shift($start_range);
        $end = array_shift($end_range);
        $result = [];
        foreach ($index as $i => $next_level) {
            if ($i >= $start && $i <= $end) {
                if (isset($next_level['url'])) {
                    $result[] = $next_level;
                } else {
                    if ($i !== $start) {
                        $s_range = array_fill(0, count($start_range), 0);
                    } else {
                        $s_range = $start_range;
                    }

                    if ($i !== $end) {
                        $e_range = array_fill(0, count($end_range), 100);
                    } else {
                        $e_range = $end_range;
                    }

                    $result = array_merge($result, $this->get_images_range($s_range, $e_range, $next_level));
                }
            }
        }

        return $result;
    }


}