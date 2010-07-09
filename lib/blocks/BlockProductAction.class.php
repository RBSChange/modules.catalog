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
		$context = $this->getPage();
		$isOnDetailPage = TagService::getInstance()->hasTag($context->getPersistentPage(), 'functional_catalog_product-detail');
		$product = $this->getDocumentParameter();
		if ($product === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			if ($isOnDetailPage)
			{
				HttpController::getInstance()->redirect("website", "Error404");
			}
			return website_BlockView::NONE;
		}
		$request->setAttribute('isOnDetailPage', $isOnDetailPage);
		
		// Prepare display configuration.
		$displayConfig = array();
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$request->setAttribute('shop', $shop);
		$displayConfig['showPricesWithTax'] = $this->getShowPricesWithTax($shop);
		$displayConfig['showPricesWithoutTax'] = $this->getShowPricesWithoutTax($shop);
		$displayConfig['showPricesWithAndWithoutTax'] = $displayConfig['showPricesWithTax'] && $displayConfig['showPricesWithoutTax'];
		$displayConfig['showShareBlock'] = $this->getShowShareBlock();
		$request->setAttribute('displayConfig', $displayConfig);
		if ($isOnDetailPage)
		{
			catalog_ModuleService::getInstance()->addConsultedProduct($product);
		}
		$request->setAttribute('baseconfiguration', $this->getConfiguration());
		$request->setAttribute('primaryshelf', catalog_ProductService::getInstance()->getPrimaryShelf($product, $shop->getWebsite()));
		$request->setAttribute('cmpref', $product->getId());
		return $this->forward($product->getDetailBlockModule(), $product->getDetailBlockName());
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return Boolean
	 */
	private function getShowPricesWithTax($shop)
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
	private function getShowPricesWithoutTax($shop)
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