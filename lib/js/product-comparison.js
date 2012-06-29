jQuery(document).ready(function() {
	// Checkbox change.
	jQuery('.products-comparison-list .product-selector').change(function() { 
		// If the box is check and the quantity equals 0, set it to 1.
		if (this.checked == true)
		{
			jQuery('#comparison_quantities_'+this.value).each(function() { 
				this.value = Math.max(parseInt(this.value), 1);
			});
		}
		// If the box is unchecked, set the quantity to 0.
		else
		{
			jQuery('#comparison_quantities_'+this.value).each(function() { 
				this.value = 0;
			});
		}
	});

	// Quantity change.
	jQuery('.products-comparison-list .quantity-selector').change(function() {
		// If there is no value for the quantity, set it to 0.
		if (this.value == '')
		{
			this.value = 0;
		}
		
		// Quantity equals 0, uncheck the box, else check it.
		var node = this;
		var productId = this.getAttribute('data-product-id');
		jQuery('#comparison_selected_product_' + productId).each(function() {
			this.checked = (parseInt(node.value) > 0);
		});
	});
});

/**
 * @param DOMNode node
 * @param integer productId
 */
function onSingleAddToCartButtonClick(node, productId)
{
	if (!node.form.hasAttribute('submitted'))
	{
		jQuery('.products-comparison-list .product-selector').each(function () {
			this.checked = (this.value == productId);
		});
		jQuery('.products-comparison-list .quantity-selector').each(function() {
			var productId2 = this.getAttribute('data-product-id');
			if (productId2 == productId)
			{
				this.value = (parseInt(node.value) > 0) ? node.value : 1;
			}
			else
			{
				this.value = 0;
			}
		});
		node.form.setAttribute('action', node.form.getAttribute('data-addtocart-url'));
		jQuery(node.form).submit();
	}
}

/**
 * @param DOMNode node
 */
function onGlobalAddToCartButtonClick(node)
{
	if (!node.form.hasAttribute('submitted'))
	{
		node.form.setAttribute('action', node.form.getAttribute('data-addtocart-url'));
		jQuery(node.form).submit();
	}
}

/**
 * @param DOMNode node
 */
function onGlobalAddToFavoriteButtonClick(node)
{
	if (!node.form.hasAttribute('submitted'))
	{
		node.form.listName.value = 'catalog_favorite';
		jQuery(node.form).submit();
	}
}

/**
 * @param DOMNode node
 * @return boolean
 */
function onFormSubmitted(node)
{
	if (node.hasAttribute('submitted'))
	{
		return false;
	}
	node.setAttribute('submitted', 'true'); 
	return true;
}