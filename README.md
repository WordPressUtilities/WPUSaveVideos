# WPUSaveVideos

Save video thumbnails from Youtube, Dailymotion & Vimeo.
Add a lightweight responsive iframe loader with the video preview instead of the heavy oembed basic player.


## Enable custom oembed responsive player (beta).

Add this code in your functions.php theme file, or your favorite mu-plugin.

```php
add_filter('wpusavevideos_enable_oembed_player', '__return_true', 10, 1);
```

## Set first video image as post thumbnail.

Add this code in your functions.php theme file, or your favorite mu-plugin.

```php
add_filter('wpusavevideos_set_post_thumbnail', '__return_true', 10, 1);
```
