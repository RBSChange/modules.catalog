<?php
/**
 * @package modules.catalog
 * @method catalog_ListShopsService getInstance()
 */
class catalog_ListShopsService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array();
		foreach (catalog_ShopService::getInstance()->createQuery()->find() as $shop)
		{
			/* @var $shop catalog_persistentdocument_shop */
			$items[] = new list_Item(
				$shop->isContextLangAvailable() ? $shop->getLabel() : $shop->getVoLabel(),
				$shop->getId()
			);
		}
		return $items;
	}
}