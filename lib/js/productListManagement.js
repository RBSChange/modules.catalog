// Checkbox change.
jQuery(document).ready(function() {
	jQuery('.product-selector').change(function() { 
		// If the box is check and the quantity equals 0, set it to 0.
		if (this.checked == true)
		{
			jQuery(this).parents('.product-line').find('.quantity-selector').each(function() { 
				if (parseInt(this.value) == 0)
				{
					this.value = '1';
				}
			});
		}
		// If the box is unchecked, set the quantity to 0.
		else
		{
			jQuery(this).parents('.product-line').find('.quantity-selector').each(function() { 
				this.value = '0';
			});
		}
	});
});

// Quantity change.
jQuery(document).ready(function() {
	jQuery('.quantity-selector').change(function() { 
		// If there is no value for the quantity, set it to 0.
		if (this.value == '')
		{
			this.value = 0;		
		}	
		
		// Quantity equals 0, uncheck the box.
		if (parseInt(this.value) == 0)
		{
			jQuery(this).parents('.product-line').find('.product-selector').each(function() { 
				this.checked = false;
			});
		}
		// Else check the box.
		else
		{
			jQuery(this).parents('.product-line').find('.product-selector').each(function() { 
				this.checked = true;
			});
		}
	});
});