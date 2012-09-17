<?php
/**
 * catalog_BlockProductpromotionAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockProductpromotionAction extends catalog_BlockProductlistBaseAction
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
		return LocaleService::getInstance()->trans('m.catalog.frontoffice.productpromotion-label', array('ucf'));
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
		$productIds = $this->getConfiguration()->getProductsIds();
		return catalog_ProductService::getInstance()->filterIdsForDisplay($productIds, $shop);
	}
	
	/**
	 * @return boolean
	 */
	protected function getShowIfNoProduct()
	{
		return false;
	}
}