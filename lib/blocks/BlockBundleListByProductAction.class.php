<?php
/**
 * catalog_BlockBundleListByProductAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockBundleListByProductAction extends catalog_BlockProductlistBaseAction
{
	/**
	 * @param f_mvc_Response $response
	 * @return integer[]
	 */
	protected function getProductIdArray($request)
	{
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
		$product = $this->getDocumentParameter();
		if ($shop === null || !($product instanceof catalog_persistentdocument_product) || !$product->isPublished())
		{
			return array();
		}
		 
		$bundleIds = catalog_BundleproductService::getInstance()->getDisplayableIdsByContainedProduct($shop, $product);
		$kitIds = catalog_KitService::getInstance()->getDisplayableIdsByContainedProduct($shop, $product);
		return array_merge($bundleIds, $kitIds);
	}
}