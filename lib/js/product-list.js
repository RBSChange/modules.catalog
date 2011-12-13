jQuery(document).ready(function() {
	jQuery('.hidden-without-js').removeClass('hidden-without-js');
	jQuery('.hidden-with-js').hide();
});

/**
 * @param DOMNode node
 */
function onGlobalAddToComparisonButtonClick(node)
{
	if (!node.form.hasAttribute('submitted'))
	{
		node.form.setAttribute('action', node.form.getAttribute('data-addtocomparison-url'));
		jQuery(node.form).submit();
	}
}