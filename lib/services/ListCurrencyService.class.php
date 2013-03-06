<?php
/**
 * @package module.catalog
 */
class catalog_ListCurrencyService extends BaseService
{
	/**
	 * @var catalog_ListCurrencyService
	 */
	private static $instance;

	/**
	 * @return catalog_ListCurrencyService
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
	public function getItems()
	{
		$items = array();
		foreach (catalog_CurrencyService::getInstance()->getPublished() as $currency)
		{
			/* @var $currency catalog_persistentdocument_currency */
			$items[] = new list_Item(
				$currency->getLabel() . ' ('. $currency->getSymbol() .')',
				$currency->getId()
			);
		}
		return $items;
	}
}