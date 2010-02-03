$(function () {
	$("[id^='productVisualBlock']").click(function () {
			var blockId = $(this).attr('id');			
			var blockId = blockId.substring(blockId.indexOf('_')+1);

			$("[id^='mainVisualBlock']").hide();
			$('div#mainVisualBlock_'+blockId).show();
			
			$('#product-pictures > div').each(function() {
				var blockLiId = $(this).attr('id');
				var blockLiId = blockLiId.substring(blockLiId.indexOf('_')+1);
				
				if(blockLiId != blockId)
				{
					$(this).hide();
				}
				else
				{
					$(this).show();
				}
			});
			
			//$(this).swapMainVisualBlock(visual);
			return false;
	});
});