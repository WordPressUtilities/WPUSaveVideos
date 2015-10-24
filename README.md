# WPUSaveVideos

Save video thumbnails from Youtube, Dailymotion & Vimeo.


## Enable custom oembed responsive player (beta).

Add this code in your functions.php theme file, or your favorite mu-plugin.

```php
add_filter('wpusavevideos_enable_oembed_player', '__return_true', 10, 1);
```