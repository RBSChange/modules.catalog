<?php
/**
 * catalog_BlockStandaloneproductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockStandaloneproductAction extends catalog_BlockProductAction
{
	/**
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	public function execute($request, $response)
	{
		$shop = $this->getConfiguration()->getShop();
		if (!($shop instanceof catalog_persistentdocument_shop))
		{
			Framework::warn(__METHOD__ . ' Invalid shop parameter: '. $this->getConfiguration()->getShopId());
			return website_BlockView::NONE;
		}
		
		$product = $this->getDocumentParameter();
		if ($product instanceof catalog_persistentdocument_declinedproduct)
		{
			$product = catalog_ProductdeclinationService::getInstance()->getPublishedDefaultDeclinationInShop($product, $shop);
		}
		
		if (!($product instanceof catalog_persistentdocument_product))
		{
			Framework::warn(__METHOD__ . ' Invalid product parameter: '. $this->getDocumentIdParameter());
			return website_BlockView::NONE;	
		}
		
		$compiledProduct = catalog_CompiledproductService::getInstance()->createQuery()
			->add(Restrictions::eq('shopId', $shop->getId()))
			->add(Restrictions::eq('product', $product))
			->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
			->add(Restrictions::eq('primary', true))->findUnique();

		if ($compiledProduct === null)
		{
			Framework::warn(__METHOD__ . ' no compiledproduct found');
			return website_BlockView::NONE;
		}		
		
		$backShop = catalog_ShopService::getInstance()->getCurrentShop();
		catalog_ShopService::getInstance()->setCurrentShop($shop);
		
		$request->setAttribute('isOnDetailPage', false);
		$this->setDisplayConfig($request, $shop);
		$request->setAttribute('baseconfiguration', $this->getConfiguration());
		$request->setAttribute('primaryshelf', $product->getDocumentService()->getShopPrimaryShelf($product, $shop));
		$request->setAttribute('cmpref', $product->getId());
		
		$result = $this->forward($product->getDetailBlockModule(), $product->getDetailBlockName());	
		catalog_ShopService::getInstance()->setCurrentShop(($backShop === null) ? false : $backShop);
		return $result;
	}
}