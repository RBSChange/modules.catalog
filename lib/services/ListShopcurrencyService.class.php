<?php
/**
 * @package module.catalog
 */
class catalog_ListShopcurrencyService extends BaseService
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
		foreach (list_StaticlistService::getInstance()->getByListId('modules_catalog/currencycode')->getItems() as $item)
		{
			$items[] = new list_Item(
				$item->getLabel() . ' ('. $item->getValue() .')',
				$item->getValue()
			);
		}
		return $items;
	}
}