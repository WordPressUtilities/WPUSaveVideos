<?php

/*
Plugin Name: WPU Save Videos
Plugin URI: http://github.com/Darklg/WPUtilities
Description: Save Videos thumbnails.
Version: 0.7
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUSaveVideos {

    private $hosts = array(
        'youtube' => array(
            'youtu.be',
            'youtube.com',
            'www.youtube.com'
        ) ,
        'vimeo' => array(
            'vimeo.com',
            'www.vimeo.com'
        ) ,
        'dailymotion' => array(
            'dailymotion.com',
            'www.dailymotion.com'
        )
    );

    private $no_save_posttypes = array(
        'revision',
        'attachment'
    );

    function __construct() {
        add_action('save_post', array(&$this,
            'save_post'
        ) , 10, 3);
        if (apply_filters('wpusavevideos_enable_oembed_player', false)) {
            add_action('wp_enqueue_scripts', array(&$this,
                'load_assets'
            ));
            add_filter('embed_oembed_html', array(&$this,
                'embed_oembed_html'
            ) , 99, 4);
        }
    }

    function save_post($post_id, $post) {
        if (!is_object($post)) {
            return;
        }

        if (!is_numeric($post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (in_array($post->post_type, $this->no_save_posttypes)) {
            return;
        }

        /* Get current video list */
        $videos = unserialize(get_post_meta($post_id, 'wpusavevideos_videos', 1));

        if (!is_array($videos)) {
            $videos = array();
        }

        /* Add new videos  */
        $new_videos = $this->extract_videos_from_text($post->post_content);

        foreach ($new_videos as $id => $new_video) {
            if (!array_key_exists($id, $videos)) {
                $new_video['thumbnail'] = $this->retrieve_thumbnail($new_video['url'], $post_id);
                if ($new_video['thumbnail'] !== false) {
                    $videos[$id] = $new_video;
                }
            }
        }

        /* Save video list */
        update_post_meta($post_id, 'wpusavevideos_videos', serialize($videos));
    }

    function extract_videos_from_text($text) {

        $hosts = array();
        foreach ($this->hosts as $new_hosts) {
            $hosts = array_merge($hosts, $new_hosts);
        }

        $videos = array();
        $urls = wp_extract_urls($text);
        foreach ($urls as $url) {

            // Get URL Key
            $url_key = md5($url);
            $url_parsed = parse_url($url);

            // No valid host
            if (!isset($url_parsed['host'])) {
                continue;
            }

            // Test host
            if (in_array($url_parsed['host'], $hosts)) {
                $videos[$url_key] = array(
                    'url' => $url
                );
            }
        }

        return $videos;
    }

    function retrieve_thumbnail($video_url, $post_id) {

        $thumbnail_details = $this->retrieve_thumbnail_details($video_url);

        if (is_array($thumbnail_details)) {
            $thumb_id = $this->media_sideload_image($thumbnail_details['url'], $post_id, $thumbnail_details['title']);
            if ($thumbnail_details['width'] > 0 && $thumbnail_details['height'] > 0) {
                $percent_ratio = 100 / ($thumbnail_details['width'] / $thumbnail_details['height']);
                add_post_meta($thumb_id, 'wpusavevideos_ratio', $percent_ratio);
            }
            return $thumb_id;
        }

        return false;
    }

    function retrieve_thumbnail_details($video_url) {

        $url_parsed = parse_url($video_url);

        if (!isset($url_parsed['host'])) {
            return '';
        }

        // Extract for youtube
        if (in_array($url_parsed['host'], $this->hosts['youtube'])) {
            $youtube_id = $this->parse_yturl($video_url);
            if ($youtube_id !== false) {

                // Weird API
                $youtube_response = wp_remote_get('http://www.youtube.com/get_video_info?video_id=' . $youtube_id);
                parse_str(wp_remote_retrieve_body($youtube_response) , $youtube_details);
                if (is_array($youtube_details) && isset($youtube_details['title'], $youtube_details['iurlhq'])) {
                    $url_img = $youtube_details['iurlhq'];
                    if (isset($youtube_details['iurlmaxres'])) {
                        $url_img = $youtube_details['iurlmaxres'];
                    }

                    $width = 0;
                    $height = 0;
                    // Try to retrieve the video dimensions
                    if (isset($youtube_details['fmt_list'])) {
                        $fmt_list = explode('/', $youtube_details['fmt_list']);
                        foreach ($fmt_list as $fmt_info) {
                            $fmt_info_details = explode('x', $fmt_info);
                            if (is_array($fmt_info_details) && isset($fmt_info_details[1])) {
                                $width = $fmt_info_details[0];
                                $height = $fmt_info_details[1];
                                break;
                            }
                        }
                    }

                    return array(
                        'url' => $url_img,
                        'title' => $youtube_details['title'],
                        'width' => $width,
                        'height' => $height
                    );
                }

                // Default API
                return array(
                    'url' => 'http://img.youtube.com/vi/' . $youtube_id . '/0.jpg',
                    'title' => $youtube_id,
                    'width' => 0,
                    'height' => 0
                );
            }
        }

        // Extract for vimeo
        if (in_array($url_parsed['host'], $this->hosts['vimeo'])) {
            $vimeo_url = explode('/', $url_parsed['path']);
            $vimeo_id = false;
            foreach ($vimeo_url as $url_part) {
                if (is_numeric($url_part)) {
                    $vimeo_id = $url_part;
                }
            }

            $vimeo_details = array();
            if (is_numeric($vimeo_id)) {
                $vimeo_response = wp_remote_get("http://vimeo.com/api/v2/video/" . $vimeo_id . ".json");
                $vimeo_details = json_decode(wp_remote_retrieve_body($vimeo_response));
            }

            if (isset($vimeo_details[0], $vimeo_details[0]->thumbnail_large)) {
                return array(
                    'url' => $vimeo_details[0]->thumbnail_large,
                    'title' => $vimeo_details[0]->title,
                    'width' => $vimeo_details[0]->width,
                    'height' => $vimeo_details[0]->height
                );
            }
        }

        // Extract for dailymotion
        if (in_array($url_parsed['host'], $this->hosts['dailymotion'])) {
            $daily_id = strtok(basename($url_parsed['path']) , '_');
            $daily_details = array();

            if (!empty($daily_id)) {
                $daily_response = wp_remote_get("https://api.dailymotion.com/video/" . $daily_id . "?fields=thumbnail_720_url,title,aspect_ratio");
                $daily_details = json_decode(wp_remote_retrieve_body($daily_response));
            }

            if (is_object($daily_details) && isset($daily_details->thumbnail_720_url)) {

                $width = 0;
                $height = 0;
                // Try to retrieve the video dimensions through the aspect ratio
                if (isset($daily_details->aspect_ratio)) {
                    $height = 300;
                    $width = intval(floor($height * $daily_details->aspect_ratio));
                }

                return array(
                    'url' => $daily_details->thumbnail_720_url,
                    'title' => $daily_details->title,
                    'width' => $width,
                    'height' => $height
                );
            }
        }

        return '';
    }

    /**
     *  Check if input string is a valid YouTube URL
     *  and try to extract the YouTube Video ID from it.
     *  @author  Stephan Schmitz <eyecatchup@gmail.com>
     *  @param   $url   string   The string that shall be checked.
     *  @return  mixed           Returns YouTube Video ID, or (boolean) false.
     */
    function parse_yturl($url) {
        $pattern = '#^(?:https?://|//)?(?:www\.|m\.)?(?:youtu\.be/|youtube\.com/(?:embed/|v/|watch\?v=|watch\?.+&v=))([\w-]{11})(?![\w-])#';
        preg_match($pattern, $url, $matches);
        return (isset($matches[1])) ? $matches[1] : false;
    }

    function media_sideload_image($file, $post_id, $desc = '') {

        // Set variables for storage, fix file filename for query strings.
        preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches);

        $tmp = download_url($file);
        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array();
        $file_array['name'] = basename($matches[0]);

        // Download file to temp location.
        $file_array['tmp_name'] = $tmp;

        // If error storing temporarily, return an error.
        if (is_wp_error($file_array['tmp_name'])) {
            return false;
        }

        // Do the validation and storage stuff.
        $id = media_handle_sideload($file_array, $post_id, $desc);

        // If error storing permanently, unlink.
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
        }

        return $id;
    }

    /* ----------------------------------------------------------
      Oembed lite player
    ---------------------------------------------------------- */

    function load_assets() {
        wp_enqueue_script('wpusavevideo_oembed_script', plugins_url('assets/script.js', __FILE__) , array(
            'jquery'
        ));
        wp_register_style('wpusavevideo_oembed_style', plugins_url('assets/style.css', __FILE__));
        wp_enqueue_style('wpusavevideo_oembed_style');
    }

    function embed_oembed_html($html, $url, $attr, $post_id) {
        if (is_admin()) {
            return $html;
        }
        $wpusavevideos_videos = unserialize(get_post_meta($post_id, 'wpusavevideos_videos', 1));
        foreach ($wpusavevideos_videos as $video_url) {
            if ($video_url['url'] != $url) {
                continue;
            }
            preg_match('/src="(.*)"/isU', $html, $matches);
            if (!isset($matches[1])) {
                continue;
            }
            $embed_url = $matches[1];
            $image = wp_get_attachment_image_src($video_url['thumbnail'], 'full');
            if (!isset($image[0])) {
                continue;
            }
            $parse_url = parse_url($url);
            if (in_array($parse_url['host'], $this->hosts['youtube'])) {
                $embed_url.= '&autoplay=1';
            }
            if (in_array($parse_url['host'], $this->hosts['vimeo'])) {
                $embed_url.= '?autoplay=1';
            }
            if (in_array($parse_url['host'], $this->hosts['dailymotion'])) {
                $embed_url.= '?autoplay=1';
            }
            $style = '';
            $ratio = get_post_meta($video_url['thumbnail'] , 'wpusavevideos_ratio', 1);
            // Only common values ( more than 1 digit )
            if (strlen($ratio) >= 2) {
                $style = 'padding-top:' . $ratio . '%;';
            }

            return '<div class="wpusv-embed-video" data-embed="' . $embed_url . '" style="' . $style . '">' . '<span class="cover" style="background-image:url(' . $image[0] . ');" >' . '<button class="wpusv-embed-video-play"></button>' . '</span>' . '</div>';
        }

        return $html;
    }

    /* Uninstall */

    function uninstall() {
        delete_post_meta_by_key('wpusavevideos_videos');
    }
}

$WPUSaveVideos = new WPUSaveVideos();
