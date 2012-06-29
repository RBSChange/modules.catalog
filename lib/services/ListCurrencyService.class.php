<?php
/**
 * @package modules.catalog
 * @method catalog_ListCurrencyService getInstance()
 */
class catalog_ListCurrencyService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @return list_Item[]
	 */
	public final function getItems()
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