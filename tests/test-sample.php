<?php
class SampleTest extends WP_UnitTestCase {
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

    function test_extractvideofromtext() {

        $videos = array(
            'https://www.youtube.com/watch?v=azazazazaza',
            'https://vimeo.com/124548668',
            'https://www.dailymotion.com/video/x2iqvk_az',
        );

        $text = '<p>' . implode(' ', $videos) . '</p>';
        $extracted_videos = $this->plugin->extract_videos_from_text($text);

        foreach ($videos as $video) {
            $this->assertEquals(true, array_key_exists(md5($video) , $extracted_videos));
        }
    }

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
}

