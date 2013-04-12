<?php
/**
 * catalog_BlockFavoriteProductListAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockFavoriteProductListAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @param shop_persistentdocument_shop $shop
	 * @return array
	 */
	protected function generateDisplayConfig($shop)
	{
		$displayConfig = parent::generateDisplayConfig($shop);
		$displayConfig['globalButtons'][] = $this->getButtonInfo('removeFromList', 'remove-from-list');
		$displayConfig['globalButtons'][] = $this->getButtonInfo('replaceList', 'remove-others-from-list');
		$displayConfig['globalButtons'][] = $this->getButtonInfo('clearList', 'clear-list');
		$displayConfig['showCheckboxes'] = true;
		return $displayConfig;
	}
	
	/**
	 * @param f_mvc_Response $response
	 * @return catalog_persistentdocument_product[]
	 */
	protected function getProductIdArray($request)
	{		 
		// If not logged in, display the warning.
		$ls = LocaleService::getInstance();
		if (users_UserService::getInstance()->getCurrentFrontEndUser() === null)
		{
			$this->addError($ls->transFO('m.catalog.frontoffice.warning-list-not-persisted', array('ucf')));
		}
		$request->setAttribute('blockTitle', $ls->transFO('m.catalog.frontoffice.my-favorite-products', array('ucf')));
		$request->setAttribute('listType', catalog_ProductList::FAVORITE);

		return catalog_ModuleService::getInstance()->getFavoriteProductIds();
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param array $displayConfig
	 * @param catalog_persistentdocument_shop $shop
	 * @param array $quantities
	 */
	protected function handleActions($request, $displayConfig, $shop, $quantities)
	{
		parent::handleActions($request, $displayConfig, $shop, $quantities);
		
		// Remove from list.
		if ($request->hasParameter('removeFromList'))
		{
			$params = array(
				'mode' => 'remove',
				'refererPageId' => $this->getContext()->getId(),
				'listName' => catalog_ProductList::FAVORITE,
				'backurl' => LinkHelper::getCurrentUrl(),
				'productIds' => array_values($request->getParameter('selected_product'))
			);
			$url = LinkHelper::getActionUrl('catalog', 'UpdateList', $params);
			$this->redirectToUrl($url);
		}
		
		// Replace list.
		if ($request->hasParameter('replaceList'))
		{
			$params = array(
				'mode' => 'replace',
				'refererPageId' => $this->getContext()->getId(),
				'listName' => catalog_ProductList::FAVORITE,
				'backurl' => LinkHelper::getCurrentUrl(),
				'productIds' => array_values($request->getParameter('selected_product'))
			);
			$url = LinkHelper::getActionUrl('catalog', 'UpdateList', $params);
			$this->redirectToUrl($url);
		}
		
		// Clear list.
		if ($request->hasParameter('clearList'))
		{
			$params = array(
				'mode' => 'clear',
				'refererPageId' => $this->getContext()->getId(),
				'listName' => catalog_ProductList::FAVORITE,
				'backurl' => LinkHelper::getCurrentUrl()
			);
			$url = LinkHelper::getActionUrl('catalog', 'UpdateList', $params);
			$this->redirectToUrl($url);
		}
		
		// Add cart products to list.
		if ($request->hasParameter('addCartToList') && catalog_ModuleService::getInstance()->isCartEnabled())
		{
			$cart = order_CartService::getInstance()->getDocumentInstanceFromSession();
			$productIds = array();
			foreach ($cart->getCartLineArray() as $line)
			{
				/* @var $line order_CartLineInfo */
				$productIds[] = $line->getProductId();
			}			
			$params = array(
				'mode' => 'add',
				'refererPageId' => $this->getContext()->getId(),
				'listName' => catalog_ProductList::FAVORITE,
				'backurl' => LinkHelper::getTagUrl('contextual_website_website_modules_catalog_favorite-product-list'),
				'productIds' => $productIds
			);
			$url = LinkHelper::getActionUrl('catalog', 'UpdateList', $params);
			$this->redirectToUrl($url);
		}
	}

	/**
	 * @param f_mvc_Request $request
	 * @return String
	 */
	protected function getDisplayMode($request)
	{
		return 'List';
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
				$this->addedProductLabels[] = $product->getLabelAsHtml();
			}
		}
		catch (Exception $e)
		{
			$this->notAddedProductLabels[] = $product->getLabelAsHtml();
			if (Framework::isDebugEnabled())
			{
				Framework::debug($e->getMessage());
			}
		}
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getProductIdArray()
	 */
	protected function getProductArray($request)
	{
		return catalog_ModuleService::getInstance()->getFavoriteProducts();
	}
}