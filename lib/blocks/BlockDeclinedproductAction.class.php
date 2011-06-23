<?php
/**
 * catalog_BlockDeclinedproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockDeclinedproductAction extends catalog_BlockProductBaseAction
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
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		
		$customer = null;
		if (catalog_ModuleService::areCustomersEnabled())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		
		if ($request->hasNonEmptyParameter('declinationId'))
		{
			$declination = DocumentHelper::getDocumentInstance($request->getParameter('declinationId'), 'modules_catalog/productdeclination');
			$product = $declination->getRelatedDeclinedProduct();
		}
		// declinedProductId parameter is deprecated. Use declinationId instead.
		else if ($request->hasNonEmptyParameter('declinedProductId'))
		{
			$declination = DocumentHelper::getDocumentInstance($request->getParameter('declinedProductId'), 'modules_catalog/productdeclination');
			$product = $declination->getRelatedDeclinedProduct();
		}
		else
		{
			$product = $this->getDocumentParameter();
			if ($product instanceof catalog_persistentdocument_declinedproduct)
			{
				$declination = $product->getDefaultDeclination($shop);
			}
			else if ($product instanceof catalog_persistentdocument_productdeclination)
			{
				$declination = $product;
				$product = $declination->getRelatedDeclinedProduct();
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
		
		// Add to cart if needed.
		if ($request->getParameter('addToCart') !== null)
		{
			$this->addProductToCartForCurrentBlock($declination);
		}

		// Add to product list if needed.
		if ($request->getParameter('addToList') !== null)
		{
			$this->addProductToFavorites($declination);
		}
		
		$prices = $declination->getPrices($shop, $customer);
		$price = array_shift($prices);
		$quantity = max(1, intval($this->findParameterValue('quantity')));
		
		$request->setAttribute('quantity', $quantity);
		$request->setAttribute('product', $product);
		$request->setAttribute('declination', $declination);
		$request->setAttribute('defaultPrice', $price);
		$request->setAttribute('thresholdPrices', $prices);
		
		return website_BlockView::SUCCESS;
	}
}