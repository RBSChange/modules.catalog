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