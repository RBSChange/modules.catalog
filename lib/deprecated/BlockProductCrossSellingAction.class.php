<?php
/**
 * @deprecated use catalog_BlockCrossSellingAction
 */
class catalog_BlockProductCrossSellingAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @deprecated
	 */
	protected function getProductIdArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$product = $this->getDocumentParameter();
		if ($shop === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			return array();
		}
		$request->setAttribute('globalProduct', $product);
		$request->setAttribute('product', $product);

		return $product->getDisplayableCrossSellingIds($shop, $this->getConfiguration()->getCrossSellingType());
	}
	
	/**
	 * @deprecated
	 */
	protected function getShowIfNoProduct()
	{
		return false;
	}
	
	/**
	 * @deprecated
	 */
	protected function getBlockTitle()
	{
		$title = $this->getConfigurationValue('blockTitle', null);
		if ($title)
		{
			return f_util_HtmlUtils::textToHtml($title);
		}
		return $this->getTypeLabel();
	}
	
	/**
	 * @deprecated
	 */
	protected function getFullListLink()
	{
		$product = $this->getDocumentParameter();
		if (!($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			return null;
		}
		$params = array('catalogParam[cmpref]' => $product->getId(), 
			'catalogParam[relationType]' => $this->getConfiguration()->getCrossSellingType());
		$url = LinkHelper::getTagUrl('functional_catalog_crosssellinglist-page', null, $params);
		$label = LocaleService::getInstance()->transFO('m.catalog.frontoffice.cross-selling-list', array('ucf'), array(
			'type' => $this->getTypeLabel()));
		return array('url' => $url, 'label' => $label);
	}
	
	/**
	 * @deprecated
	 */
	private function getTypeLabel()
	{
		$sellingType = $this->getConfiguration()->getCrossSellingType();
		$list = list_ListService::getInstance()->getByListId('modules_catalog/crosssellingtypes');
		return f_util_HtmlUtils::textToHtml($list->getItemByValue($sellingType)->getLabel());
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getProductIdArray()
	 */
	protected function getProductArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$product = $this->getDocumentParameter();
		if ($shop === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			return array();
		}
		$request->setAttribute('globalProduct', $product);
		$request->setAttribute('product', $product);
		
		$configuration = $this->getConfiguration();
		$sellingType = $configuration->getCrossSellingType();
		
		return $product->getDisplayableCrossSelling($shop, $sellingType, $configuration->getSortby());
	}
}