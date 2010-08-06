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
			$cms = catalog_ModuleService::getInstance();
			$cms->getProductList(catalog_ProductList::FAVORITE, true);
			$cms->getProductList(catalog_ProductList::CONSULTED, true);
		}
	}
}