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
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return array<list_Item>
	 */
	public final function getItems()
	{
		$items = array();
		foreach (catalog_CurrencyService::getInstance()->getCurrencySymbolsArray() as $code => $symbol)
		{
			$items[] = new list_Item(
				$code . ' ('. $symbol .')',
				$code
			);
		}
		return $items;
	}
}