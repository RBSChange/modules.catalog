<?php
/**
 * catalog_BlockFavoriteProductListAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockFavoriteProductListAction extends catalog_BlockProductlistBaseAction
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
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		 
		// Remove product if needed.
		if ($request->hasParameter('removeFromList'))
		{
			foreach ($request->getParameter('selected_product') as $id)
			{
				$this->removeFromFavorite(DocumentHelper::getDocumentInstance($id));
			}
			$this->addMessagesToBlock('remove-from-list');
		}
		
		// Add cart products to list.
		if ($request->hasParameter('addCartToList') && catalog_ModuleService::getInstance()->isCartEnabled())
		{
			$cart = order_CartService::getInstance()->getDocumentInstanceFromSession();
			foreach ($cart->getCartLineArray() as $line)
			{
				$product = $line->getProduct();
				catalog_ModuleService::getInstance()->addFavoriteProduct($product);
			}
			HttpController::getInstance()->redirectToUrl(LinkHelper::getTagUrl('contextual_website_website_modules_catalog_favorite-product-list'));
			return website_BlockView::NONE;
		}

		// If not logged in, display the warning.
		if (users_UserService::getInstance()->getCurrentFrontEndUser() === null)
		{
			$this->addError(f_Locale::translate('&modules.catalog.frontoffice.Warning-list-not-persisted;'));
		}
		
		// Prepare display configuration.
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$displayConfig = $this->getDisplayConfig($shop);
		$displayConfig['globalButtons'][] = $this->getButtonInfo('removeFromList', 'remove-from-list');
		$displayConfig['showCheckboxes'] = true;
		$request->setAttribute('displayConfig', $displayConfig);
		 
		$request->setAttribute('shop', catalog_ShopService::getInstance()->getCurrentShop());
		$request->setAttribute('products', catalog_ModuleService::getInstance()->getFavoriteProducts());
		$request->setAttribute('blockTitle', f_Locale::translate('&modules.catalog.frontoffice.My-favorite-products;'));
		$request->setAttribute('blockView', 'table');
				
		return $this->forward('catalog', 'productlist');
	}
	
	/**
	 * @param catalog_persistentdocument_product $product
	 */
	private function removeFromFavorite($product)
	{
		try
		{
			if (catalog_ModuleService::getInstance()->removeFavoriteProduct($product))
			{
				$this->addedProductLabels[] = $product->getLabel();
			}
		}
		catch (Exception $e)
		{
			$this->notAddedProductLabels[] = $product->getLabel();
			if (Framework::isDebugEnabled())
			{
				Framework::debug($e->getMessage());
			}
		}
	}
}