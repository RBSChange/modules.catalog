<?php

/**
 * @package module.catalog
 */
class catalog_ListShopsService extends BaseService
{
	/**
	 * @var catalog_ListShopsService
	 */
	private static $instance;

	/**
	 * @return catalog_ListShopsService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return array<list_Item>
	 */
	public final function getItems()
	{
		$items = array();
		foreach (catalog_ShopService::getInstance()->createQuery()->find() as $shop)
		{
			$items[] = new list_Item(
				$shop->getLabel(),
				$shop->getId()
			);
		}
		return $items;
	}
}