registerDOMLoadedCallback(function (selectorPrefix) {
	jQuery(selectorPrefix + "[id^='product-thumbnail']").click(function () {
		var blockId = jQuery(this).attr('id');			
		var blockId = blockId.substring('product-thumbnail-'.length);
		jQuery("[id^='product-visual']").hide();
		jQuery('#product-visual-'+blockId).show();
		return false;
	});
});

function catalog_openAddToCartPopin(popinPageId, productId)
{
	openPopIn(popinPageId, {catalogParam: {cmpref: productId}}, {title:'${trans:m.catalog.fo.add-to-cart-popin,ucf}', modal:true, width:925, minWidth:925}, null);
}