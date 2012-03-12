<?php
/**
 * catalog_BlockBundleproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockBundleproductAction extends catalog_BlockProductBaseAction
{
	function getCacheKeyParameters($request)
	{
		return array("bundleditemid" => $request->getParameter("bundleditemid"));
	}
	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		/* @var $product catalog_persistentdocument_bundleproduct */
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
		
		$request->setAttribute('product', $product);
		
		$prices = $product->getPricesForCurrentShopAndCustomer();
		if (count($prices))
		{
			$price = array_shift($prices);
			$request->setAttribute('defaultPrice', $price);
			$request->setAttribute('differencePrice', $product->getPriceDifference($shop, $shop->getCurrentBillingArea(), $customer));
			$request->setAttribute('thresholdPrices', $prices);
		}
		
		if ($request->hasParameter('bundleditemid'))
		{
			$bundleditem = catalog_persistentdocument_bundleditem::getInstanceById($request->getParameter('bundleditemid'));
			$request->setAttribute('bundleditem', $bundleditem);
			$request->setAttribute('bundledproduct', $bundleditem->getProduct());
			return 'Bundleditem'; 
		}
		
		return website_BlockView::SUCCESS;
	}
}
