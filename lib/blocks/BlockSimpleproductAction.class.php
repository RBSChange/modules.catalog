<?php
/**
 * catalog_BlockSimpleproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockSimpleproductAction extends catalog_BlockProductBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		/* @var $product catalog_persistentdocument_simpleproduct */
		$product = $this->getDocumentParameter();
		
		// @deprecated this should not be used anymore. See order_AddToCartAction
		if ($request->getParameter('addToCart') !== null)
		{
			$this->addProductToCartForCurrentBlock($product);
		}

		// @deprecated this should not be used anymore. See catalog_UpdateListAction
		if ($request->getParameter('addToList') !== null)
		{
			$this->addProductToFavorites($product);
		}
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