<?php
/**
 * catalog_ProductlistPersistenceListener
 * @package modules.catalog
 */
class catalog_ProductlistPersistenceListener
{
	/**
	 * @param Mixed $sender
	 * @param Array<Mixed> $params
	 */
	public function onUserLogin($sender, $params)
	{
		if ($params['user'] instanceof users_persistentdocument_frontenduser) 
		{
			$productList = catalog_ProductlistService::getInstance()->getCurrentProductList();
			catalog_ModuleService::getInstance()->mergeFavoriteProductWithList($productList);
		}
	}
}