<?php
/**
 * catalog_BlockDeclinedproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockDeclinedproductAction extends catalog_BlockProductBaseAction
{
	/**
	 * @param f_mvc_Request $request
	 */
	public function initialize($request)
	{
		parent::initialize($request);
		
		if ($request->hasNonEmptyParameter('declinationId') && $request->getParameter('declinationId') != $this->getDocumentIdParameter())
		{
			$this->redirectToDeclination($request->getParameter('declinationId'));
		}
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @return array
	 */
	public function getCacheKeyParameters($request)
	{
		return array("quantity" => $request->getParameter('quantity'));
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
		if (catalog_ModuleService::getInstance()->areCustomersEnabled())
		{
			$customer = customer_CustomerService::getInstance()->getCurrentCustomer();
		}
		
		$product = $this->getDocumentParameter();
		if ($product instanceof catalog_persistentdocument_productdeclination)
		{
			$declination = $product;
			$product = $declination->getDeclinedProduct();
		}
				
		if (!$product || !$declination)
		{
			if ($request->getAttribute('isOnDetailPage'))
			{
				HttpController::getInstance()->redirect("website", "Error404");
			}
			return website_BlockView::NONE;
		}
		
		// Add canonical to default shown in list.
		if ($request->getAttribute('isOnDetailPage') && Framework::getConfigurationValue('modules/catalog/addCanonicalOnDeclinations', 'true') == 'true')
		{
			$cp = $declination->getContextualCompiledProduct();
			if (!$cp->getShowInList())
			{
				$doc = $declination->getDocumentService()->getShownInListByCompiledProduct($cp);
				if ($doc)
				{
					$this->getContext()->addLink('canonical', null, LinkHelper::getDocumentUrl($doc));
				}
			}
		}
		
		// @deprecated this should not be used anymore. See order_AddToCartAction
  		if ($request->getParameter('addToCart') !== null)
  		{
  			$this->addProductToCartForCurrentBlock($declination);
  		}
		
		// @deprecated this should not be used anymore. See catalog_UpdateListAction
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
		
		return website_BlockView::SUCCESS;
	}
	
	/**
	 * @param integer $declinationId
	 */
	protected function redirectToDeclination($declinationId)
	{
		$declination = DocumentHelper::getDocumentInstanceIfExists($declinationId);
		if ($declination instanceof catalog_persistentdocument_productdeclination)
		{
			$compiledProduct = $declination->getContextualCompiledProduct();
			if ($compiledProduct)
			{
				$this->redirectToUrl(LinkHelper::getDocumentUrl($compiledProduct));
				exit;
			}
		}
	}
}