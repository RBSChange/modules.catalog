<?php
/**
 * catalog_BlockProductpromotionAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductpromotionAction extends catalog_BlockProductlistBaseAction 
{
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		$products = $this->getConfiguration()->getProducts();
		if (f_util_ArrayUtils::isEmpty($products))
		{
			return website_BlockView::NONE;
		}
		$label = $this->getConfiguration()->getLabel();
		$shop =  catalog_ShopService::getInstance()->getCurrentShop();
		$request->setAttribute('shop', $shop);
		$request->setAttribute('blockTitle', f_util_StringUtils::isEmpty($label) ? f_Locale::translate('&modules.catalog.frontoffice.productpromotion-label;') : $label);
		
		if ($this->getConfiguration()->getMode() === "random")
		{
			$request->setAttribute('product', f_util_ArrayUtils::randomElement($products));
			return 'Random';
		}
		else 
		{
			$displayConfig = $this->getDisplayConfig($shop);
			$request->setAttribute('displayConfig', $displayConfig);
			$request->setAttribute('products', $products);
			$request->setAttribute('blockView', $this->getConfiguration()->getDisplayMode());
			return $this->forward('catalog', 'productlist');
		}
	}
}