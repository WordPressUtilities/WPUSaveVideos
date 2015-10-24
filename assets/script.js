jQuery(document).ready(function() {
    jQuery('.wpusv-embed-video-play').on('click', function(e) {
        e.preventDefault();
        var $this = jQuery(this),
            $cover = $this.parent(),
            parent = $this.closest('[data-embed]'),
            url = parent.attr('data-embed'),
            iframe = jQuery('<iframe frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>');
        iframe.attr('src', url).appendTo(parent);
        setTimeout(function() {
            $cover.animate({
                opacity: 0
            }, 500);
        }, 100);
        setTimeout(function() {
            $cover.remove();
        }, 700);
    });
});