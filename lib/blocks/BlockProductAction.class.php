<?php
/**
 * catalog_BlockProductAction
 * @package modules.catalog
 */
class catalog_BlockProductAction extends catalog_BlockProductBaseAction
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
		
		$request->setAttribute('isOnDetailPage', true);
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if (!($shop instanceof catalog_persistentdocument_shop))
		{
			Framework::warn(__METHOD__ . ' no current shop defined');
			HttpController::getInstance()->redirect("website", "Error404");
		}	
			
		$product = $this->getDocumentParameter();
		if ($product === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			Framework::warn(__METHOD__ . ' no product defined: ' . $this->getDocumentIdParameter());
			HttpController::getInstance()->redirect("website", "Error404");
		}
		
		
		$context = $this->getContext();
		$topicId =  $context->getParent()->getId();
		$compiledProduct = catalog_CompiledproductService::getInstance()->getByProductInContext($product, $topicId, RequestContext::getInstance()->getLang());
		if ($compiledProduct === null)
		{
			Framework::warn(__METHOD__ . ' no compiledproduct founded');
			HttpController::getInstance()->redirect("website", "Error404");
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
		$displayConfig = array();
		$request->setAttribute('shop', $shop);
		$displayConfig['showPricesWithTax'] = $this->getShowPricesWithTax($shop);
		$displayConfig['showPricesWithoutTax'] = $this->getShowPricesWithoutTax($shop);
		$displayConfig['showPricesWithAndWithoutTax'] = $displayConfig['showPricesWithTax'] && $displayConfig['showPricesWithoutTax'];
		$displayConfig['showShareBlock'] = $this->getShowShareBlock();
		$displayConfig['showAnimPictogramBlock'] = ModuleService::getInstance()->moduleExists('marketing');
		$request->setAttribute('displayConfig', $displayConfig);
	}

	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Boolean
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
	 * @return Boolean
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
	 * @return Boolean
	 */
	private function getShowShareBlock()
	{
		return ModuleService::getInstance()->isInstalled('modules_sharethis') && $this->getConfiguration()->getShowShareBlock();
	}
	
	/**
	 * @return array<String, String>
	 */
	function getMetas()
	{
		$product = $this->getDocumentParameter();
		if ($product instanceof catalog_persistentdocument_product)
		{
			return catalog_ReferencingService::getInstance()->getMetaSubstitutionsForProduct($product);
		}
		return array();
	}
}