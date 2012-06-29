<?php
/**
 * catalog_BlockProductAction
 * @package modules.catalog
 */
class catalog_BlockProductAction extends catalog_BlockProductBaseAction
{
	/**
	 * @return array<string, string>
	 */
	public function getMetas()
	{
		$product = $this->getDocumentParameter();
		if ($product instanceof catalog_persistentdocument_product)
		{
			return catalog_ReferencingService::getInstance()->getMetaSubstitutionsForProduct($product);
		}
		return array();
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return string
	 */
	public function execute($request, $response)
	{
		if ($this->isInBackoffice())
		{
			return website_BlockView::NONE;
		}
		
		$request->setAttribute('isOnDetailPage', true);
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if (!($shop instanceof catalog_persistentdocument_shop))
		{
			Framework::warn(__METHOD__ . ' no current shop defined');
			change_Controller::getInstance()->redirect("website", "Error404");
		}
			
		$product = $this->getDocumentParameter();
		if ($product === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			Framework::warn(__METHOD__ . ' no product defined: ' . $this->getDocumentIdParameter());
			change_Controller::getInstance()->redirect("website", "Error404");
		}
		
		$context = $this->getContext();
		$topicId =  $context->getParent()->getId();
		$compiledProduct = catalog_CompiledproductService::getInstance()->getByProductInContext($product, $topicId, RequestContext::getInstance()->getLang());
		if ($compiledProduct === null)
		{
			Framework::warn(__METHOD__ . ' no compiledproduct found');
			change_Controller::getInstance()->redirect("website", "Error404");
		}		
		
		$this->setDisplayConfig($request, $shop);
		catalog_ModuleService::getInstance()->addConsultedProduct($product);
		if (!$shop->getIsDefault())
		{
			$context->addCanonicalParam('shopId', $request->getParameter('shopId'), 'catalog');
			$request->setAttribute('contextShopId', $shop->getId());
		}
		
		if (!$compiledProduct->getPrimary())
		{
			$request->setAttribute('contextTopicId', $topicId);
		}
			
		$request->setAttribute('baseconfiguration', $this->getConfiguration());
		$request->setAttribute('primaryshelf', $product->getDocumentService()->getShopPrimaryShelf($product, $shop));
		$request->setAttribute('cmpref', $product->getId());
		return $this->forward($product->getDetailBlockModule(), $product->getDetailBlockName());
	}
	
	/**
	 * @param f_mvc_Request $request
	 * @param catalog_persistentdocument_shop $shop
	 */
	protected function setDisplayConfig($request, $shop)
	{
		// Prepare display configuration.
		$configuration = $this->getConfiguration();
		$displayConfig = array();
		$request->setAttribute('shop', $shop);
		$displayConfig['showPricesWithTax'] = $this->getShowPricesWithTax($shop);
		$displayConfig['showPricesWithoutTax'] = $this->getShowPricesWithoutTax($shop);
		$displayConfig['showPricesWithAndWithoutTax'] = $displayConfig['showPricesWithTax'] && $displayConfig['showPricesWithoutTax'];
		$displayConfig['showPrices'] = $displayConfig['showPricesWithTax'] || $displayConfig['showPricesWithoutTax'];
		$displayConfig['showRating'] = $configuration->getShowRating();
		$displayConfig['showAddToFavorite'] = $configuration->getShowAddToFavorite();
		$displayConfig['showAddToComparison'] = $configuration->getShowAddToComparison();
		$displayConfig['showAddLinkList'] = $displayConfig['showAddToFavorite'] || $displayConfig['showAddToComparison'];
		$displayConfig['showShareBlock'] = $this->getShowShareBlock();
		$displayConfig['showAnimPictogramBlock'] = ModuleService::getInstance()->moduleExists('marketing');
		$displayConfig['isStandalone'] = $this->isStandalone();
		$displayConfig['isPopin'] = false;
		$displayConfig['popinPageId'] = null;
		$request->setAttribute('displayConfig', $displayConfig);
	}
	
	/**
	 * @return boolean
	 */
	protected function isStandalone()
	{
		return false;
	}
		
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return boolean
	 */
	protected function getShowPricesWithTax($shop)
	{
		switch ($this->getConfiguration()->getDisplayPriceWithTax())
		{
			case 'asInShop':
				return $shop->getDisplayPriceWithTax();
			case 'true':
				return true;
			case 'false':
				return false;
		}
		return false;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return boolean
	 */
	protected function getShowPricesWithoutTax($shop)
	{
		switch ($this->getConfiguration()->getDisplayPriceWithoutTax())
		{
			case 'asInShop':
				return $shop->getDisplayPriceWithoutTax();
			case 'true':
				return true;
			case 'false':
				return false;
		}
		return false;
	}
	
	/**
	 * @return boolean
	 */
	private function getShowShareBlock()
	{
		return ModuleService::getInstance()->isInstalled('modules_sharethis') && $this->getConfiguration()->getShowShareBlock();
	}
}