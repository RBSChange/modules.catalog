<?php
/**
 * catalog_BlockSimpleproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockSimpleproductAction extends catalog_BlockProductBaseAction
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
		$product = $this->getDocumentParameter();
		
		// @deprecated this should not be used anymore. See catalog_AddToCartAction
		if ($request->getParameter('addToCart') !== null)
		{
			$this->addProductToCartForCurrentBlock($product);
		}

		// @deprecated this should not be used anymore. See catalog_AddToListAction
		if ($request->getParameter('addToList') !== null)
		{
			$this->addProductToFavorites($product);
		}
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop(); 
		
		$customer = null;
		if (catalog_ModuleService::areCustomersEnabled())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		
		$prices = $product->getPrices($shop, $customer);
		$price = array_shift($prices);
		
		$request->setAttribute('product', $product);
		$request->setAttribute('defaultPrice', $price);
		$request->setAttribute('thresholdPrices', $prices);
		
		return website_BlockView::SUCCESS;
	}
}