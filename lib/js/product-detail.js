jQuery(function () {
	jQuery("[id^='product-thumbnail']").click(function () {
		var blockId = jQuery(this).attr('id');			
		var blockId = blockId.substring('product-thumbnail-'.length);
		jQuery("[id^='product-visual']").hide();
		jQuery('#product-visual-'+blockId).show();
		return false;
	});
});