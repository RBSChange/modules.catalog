jQuery(function () {
	jQuery("[id^='productVisualBlock']").click(function () {
			var blockId = jQuery(this).attr('id');			
			var blockId = blockId.substring(blockId.indexOf('_')+1);

			jQuery("[id^='mainVisualBlock']").hide();
			jQuery('div#mainVisualBlock_'+blockId).show();
			
			jQuery('#product-pictures > div').each(function() {
				var blockLiId = jQuery(this).attr('id');
				var blockLiId = blockLiId.substring(blockLiId.indexOf('_')+1);
				
				if(blockLiId != blockId)
				{
					jQuery(this).hide();
				}
				else
				{
					jQuery(this).show();
				}
			});
			
			//jQuery(this).swapMainVisualBlock(visual);
			return false;
	});
});