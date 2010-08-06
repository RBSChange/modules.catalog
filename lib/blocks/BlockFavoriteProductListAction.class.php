<?php
/**
 * catalog_BlockFavoriteProductListAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockFavoriteProductListAction extends catalog_BlockProductlistBaseAction
{
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Array
	 */
	protected function getDisplayConfig($shop)
	{
		$displayConfig = parent::getDisplayConfig($shop);
		$displayConfig['globalButtons'][] = $this->getButtonInfo('removeFromList', 'remove-from-list');
		$displayConfig['showCheckboxes'] = true;
		$displayConfig['itemconfig']['showCheckboxes'] = true;
		return $displayConfig;
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[]
	 */
	protected function getProductArray($request)
	{		 
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
			return null;
		}

		// If not logged in, display the warning.
		if (users_UserService::getInstance()->getCurrentFrontEndUser() === null)
		{
			$this->addError(f_Locale::translate('&modules.catalog.frontoffice.Warning-list-not-persisted;'));
		}
		$request->setAttribute('blockTitle', f_Locale::translate('&modules.catalog.frontoffice.My-favorite-products;'));

		return catalog_ModuleService::getInstance()->getFavoriteProducts();
	}

	/**
	 * @param f_mvc_Request $request
	 * @return String
	 */
	protected function getDisplayMode($request)
	{
		return 'table';
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