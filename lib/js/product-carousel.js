/**
 * Adapted form: http://www.fruitbatscode.com/javascript/jquery/jquery-carousel-plugin 
 */
jQuery.fn.productCarousel = function() {
	// Bind scroll function on the click event.
	return this.each(function() {
		var me = jQuery(this);
		var ul = jQuery(me.find('ul').get(0));
		jQuery(ul).data('index', 0);
		
		// To obtain the real list width, we may use scrollWidth but it works, only with a scrolling overflow.
		ul.css('overflow', 'scroll');
		var listWidth = ul.get(0).scrollWidth;
		ul.css('overflow', 'visible');
		var visibleWidth = me.outerWidth();

		if (listWidth > visibleWidth)
		{
			jQuery(ul).data('listWidth', listWidth);		
			me.find('.arrows button').show().click(function(event) { scroll(event, me, ul); });
			scroll(null, me, ul);
		}
	});
	
	function scroll(event, container, ul)
	{
		var index = parseInt(jQuery(ul).data('index'));
		var pageWidth = container.outerWidth();
		var listWidth = jQuery(ul).data('listWidth');
		var maxIndex = Math.ceil(listWidth / pageWidth) - 1;
		if (event)
		{
			if (jQuery(event.target).hasClass('slideleft')) 
			{
				index = Math.max(0, index - 1);
			}
			else if (jQuery(event.target).hasClass('slideright'))
			{
				index = Math.min(maxIndex, index + 1);
			}		
			event.preventDefault(); // Stop event.
		}
		
		if (index == 0)
		{
			container.find('.arrows .slideleft').attr('disabled', 'disabled');
		}
		else
		{
			container.find('.arrows .slideleft').removeAttr('disabled');
		}
		if (index == maxIndex)
		{
			container.find('.arrows .slideright').attr('disabled', 'disabled');
		}
		else
		{
			container.find('.arrows .slideright').removeAttr('disabled');
		}
		
		jQuery(ul).data('index', index);
		ul.animate({left: (0 - (index * pageWidth)) + 'px'});
		return false;
	}
};

jQuery(document).ready(function() {
	jQuery('.product-carousel').productCarousel();
});