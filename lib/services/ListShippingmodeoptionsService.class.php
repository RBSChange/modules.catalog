<?php
/**
 * @package module.catalog
 */
class catalog_ListShippingmodeoptionsService extends BaseService
{
	/**
	 * @var catalog_ListShippingmodeoptionsService
	 */
	private static $instance;
	
	/**
	 * @return catalog_ListShippingmodeoptionsService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @return list_Item[]
	 */
	public function getItems()
	{
		$items = array();
		$items [] = new list_Item(f_Locale::translate('&modules.catalog.bo.lists.shippingmodeoptions.Free;'), 'free');
		$items [] = new list_Item(f_Locale::translate('&modules.catalog.bo.lists.shippingmodeoptions.Current;'), 'current');
		foreach (catalog_ShippingfilterService::getInstance()->getModesSelectedByProduct() as $mode)
		{
			$items[] = new list_Item($mode->getLabel(), $mode->getId());
		}
		return $items;
	}
	
	/**
	 * @return String
	 */
	public function getDefaultId()
	{
		return 'free';
	}
}