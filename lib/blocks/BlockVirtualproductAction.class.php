<?php
/**
 * catalog_BlockVirtualproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockVirtualproductAction extends catalog_BlockProductBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	function execute($request, $response)
	{
		$product = $this->getDocumentParameter();
		$request->setAttribute('product', $product);
		
		$prices = $product->getPricesForCurrentShopAndCustomer();
		if (count($prices))
		{
			$price = array_shift($prices);
			$request->setAttribute('defaultPrice', $price);
			$request->setAttribute('thresholdPrices', $prices);			
		}
		return website_BlockView::SUCCESS;
	}
}