<?php
/**
 * catalog_BlockDeclinedproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockDeclinedproductAction extends catalog_BlockProductBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 * @return array
	 */
	public function getCacheKeyParameters($request)
	{
		return array("declinationId" => $request->getParameter('declinationId'),
			"quantity" => $request->getParameter('quantity'));
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$customer = null;
		if (catalog_ModuleService::areCustomersEnabled())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		
		if ($request->hasNonEmptyParameter('declinationId'))
		{
			$declination = catalog_persistentdocument_productdeclination::getInstanceById($request->getParameter('declinationId'));
			$product = $declination->getDeclinedProduct();
		}
		else
		{
			$product = $this->getDocumentParameter();
			if ($product instanceof catalog_persistentdocument_productdeclination)
			{
				$declination = $product;
				$product = $declination->getDeclinedProduct();
			}
		}
				
		if (!$product || !$declination)
		{
			if ($request->getAttribute('isOnDetailPage'))
			{
				HttpController::getInstance()->redirect("website", "Error404");
			}
			return website_BlockView::NONE;
		}
		
		// @deprecated this should not be used anymore. See catalog_AddToCartAction
  		if ($request->getParameter('addToCart') !== null)
  		{
  			$this->addProductToCartForCurrentBlock($declination);
  		}
		
		// @deprecated this should not be used anymore. See catalog_AddToListAction
		if ($request->getParameter('addToList') !== null)
		{
			$this->addProductToFavorites($declination);
		}
		$quantity = max(1, intval($this->findParameterValue('quantity')));	
		$request->setAttribute('quantity', $quantity);
		$request->setAttribute('declinedproduct', $product);
		$request->setAttribute('product', $declination);
				
		$prices = $declination->getPricesForCurrentShopAndCustomer();
		if (count($prices))
		{
			$price = array_shift($prices);
			$request->setAttribute('defaultPrice', $price);
			$request->setAttribute('thresholdPrices', $prices);	
		}
		
		$price = array_shift($prices);
		return website_BlockView::SUCCESS;
	}
}