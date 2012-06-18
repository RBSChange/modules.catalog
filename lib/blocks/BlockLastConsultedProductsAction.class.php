<?php
/**
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockLastConsultedProductsAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @return string
	 */
	protected function getBlockTitle()
	{
		$label = $this->getConfiguration()->getLabel();
		if ($label)
		{
			return f_util_HtmlUtils::textToHtml($label);
		}
		return LocaleService::getInstance()->transFO('m.catalog.frontoffice.last-consulted-products', array('ucf'));
	}

	/**
	 * @param f_mvc_Response $response
	 * @return integer[] or null
	 */
	protected function getProductIdArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		if (!$shop)
		{
			return null;
		}		
		$productIds = catalog_ModuleService::getInstance()->getConsultedProductIds();
		return catalog_ProductService::getInstance()->filterIdsForDisplay($productIds, $shop);
	}
	
	/**
	 * @return boolean
	 */
	protected function getShowIfNoProduct()
	{
		return false;
	}
	
	// Deprecated.
	
	/**
	 * @deprecated use getProductIdArray()
	 */
	protected function getProductArray($request)
	{
		$productsIds = $this->getProductIdArray($request);
		if (f_util_ArrayUtils::isEmpty($productsIds))
		{
			return null;
		}
		$result = array();
		foreach ($productsIds as $id)
		{
			$result[] = DocumentHelper::getDocumentInstance($id, 'modules_catalog/product');
		}
		return $result;
	}
}