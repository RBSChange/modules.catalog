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
	 * @return String
	 */
	public function execute($request, $response)
	{
		$product = $this->getDocumentParameter();
		
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