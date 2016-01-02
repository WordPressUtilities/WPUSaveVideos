<?php
class WPUSaveVideos_PluginTest extends WP_UnitTestCase {
    public $plugin;

    function setUp() {
        parent::setUp();
        $this->plugin = new WPUSaveVideos;
    }

    function test_initplugin() {

        // Test plugin init
        do_action('init');
        $this->assertEquals(10, has_action('save_post', array(
            $this->plugin,
            'save_post'
        )));
    }

    function test_assets() {
        $this->plugin->load_assets();
        $this->assertTrue(wp_style_is('wpusavevideo_oembed_style', 'registered'));
        $this->assertTrue(wp_script_is('wpusavevideo_oembed_script', 'registered'));
    }

    /* ----------------------------------------------------------
      Extraction from text
    ---------------------------------------------------------- */

    function test_extractvideofromtext() {

        $videos = array(
            'https://www.youtube.com/watch?v=azazazazaza',
            'https://vimeo.com/109903713',
            'https://www.dailymotion.com/video/x2iqvk_az',
        );

        $text = '<p>' . implode(' ', $videos) . '</p>';
        $extracted_videos = $this->plugin->extract_videos_from_text($text);

        foreach ($videos as $video) {
            $this->assertEquals(true, array_key_exists(md5($video) , $extracted_videos));
        }
    }

    /* ----------------------------------------------------------
      Extraction from distant url
    ---------------------------------------------------------- */

    function test_thumbnails_details() {

        /* Vimeo */
        $vimeo_img = $this->plugin->retrieve_thumbnail_details('https://vimeo.com/109903713');
        $this->assertEquals(4, count($vimeo_img));

        /* Dailymotion */
        $daily_img = $this->plugin->retrieve_thumbnail_details('https://www.dailymotion.com/video/x2iqvk_az');
        $this->assertEquals(4, count($daily_img));

        /* Youtube */
        $youtube_img = $this->plugin->retrieve_thumbnail_details('https://www.youtube.com/watch?v=WxfZkMm3wcg');
        // Test if at least a result
        $this->assertEquals(4, count($youtube_img));
        // Test if using the "weird" API
        $this->assertContains('maxresdefault.jpg', $youtube_img['url']);

        /* Video without host */
        $no_host = $this->plugin->retrieve_thumbnail_details('x2iqvk_az');
        $this->assertEquals('', $no_host);

        /* Bad domain */
        $bad_host = $this->plugin->retrieve_thumbnail_details('http://github.com/az');
        $this->assertEquals('', $bad_host);


    }

    /* ----------------------------------------------------------
      URL Methods
    ---------------------------------------------------------- */

    function test_parseyturl() {

        $videos = array(
            'https://www.youtube.com/watch?v=azazazazaza&test',
            'https://www.youtube.com/watch?v=azazazazaza',
            'https://www.youtube.com/embed/azazazazaza',
            'https://www.youtube.com/v/azazazazaza',
            'https://youtu.be/azazazazaza',
        );
        foreach ($videos as $video) {
            $this->assertEquals('azazazazaza', $this->plugin->parse_yturl($video));
        }
    }

    function test_parsedailyurl() {
        $videos = array(
            'http://www.dailymotion.com/swf/video/x2iqvk_az',
            'http://www.dailymotion.com/embed/video/x2iqvk_az',
            'https://www.dailymotion.com/video/x2iqvk_az'
        );
        foreach ($videos as $video) {
            $this->assertEquals('x2iqvk', $this->plugin->parse_dailyurl($video));
        }
    }

    function test_parsevimeourl() {
        $videos = array(
            'http://player.vimeo.com/video/109903713?title=0&byline=0&portrait=0',
            'http://vimeo.com/channels/channelname/109903713',
            'http://player.vimeo.com/video/109903713',
            'https://vimeo.com/109903713',
        );
        foreach ($videos as $video) {
            $this->assertEquals('109903713', $this->plugin->parse_vimeourl($video));
        }
    }
}

