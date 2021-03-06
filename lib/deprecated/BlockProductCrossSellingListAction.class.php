<?php
/**
 * @deprecated use catalog_BlockCrossSellingListAction
 */
class catalog_BlockProductCrossSellingListAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @deprecated
	 */
	protected function getProductIdArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$product = $this->getDocumentParameter();
		if (!($product instanceof catalog_persistentdocument_product))
		{
			return array();
		}
		return $product->getDisplayableCrossSellingIds($shop, $request->getParameter('relationType'));
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
		$product = $this->getDocumentParameter();
		if (!($product instanceof catalog_persistentdocument_product))
		{
			return null;
		}
		$productUrl = LinkHelper::getDocumentUrl($product);
		$replacements = array('productLink' => '<a class="link" href="' . $productUrl . '">' . $product->getLabelAsHtml() . '</a>');
		$key = 'm.catalog.frontoffice.cross-selling-' . $this->getRequest()->getParameter('relationType');
		return LocaleService::getInstance()->transFO($key, array('ucf'), $replacements);
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getProductIdArray()
	 */
	protected function getProductArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$product = $this->getDocumentParameter();
		if (!($product instanceof catalog_persistentdocument_product))
		{
			return array();
		}
		return $product->getDisplayableCrossSelling($shop, $request->getParameter('relationType'));
	}
}