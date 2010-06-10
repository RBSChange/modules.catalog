//SLIDER
jQuery(function() {
	jQuery('#slideshow').after('<ol id="additionnal-visual">').cycle({
        fx:     'fade',
        speed:  'normal',
        timeout: 0,
        pager:  '#additionnal-visual',
        pagerAnchorBuilder: function(idx, slide) 
        {
            return '<li><a href="#"><img src="' + slide.src + '" width="40" height="40" /></a></li>';
        }
    });
});