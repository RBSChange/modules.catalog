<?php
/**
 * @package modules.catalog
 * @method catalog_ListShippingmodeoptionsService getInstance()
 */
class catalog_ListShippingmodeoptionsService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array();
		$items [] = new list_Item(LocaleService::getInstance()->trans('m.catalog.bo.lists.shippingmodeoptions.free', array('ucf')), 'free');
		$items [] = new list_Item(LocaleService::getInstance()->trans('m.catalog.bo.lists.shippingmodeoptions.current', array('ucf')), 'current');
		foreach (catalog_ShippingfilterService::getInstance()->getModesSelectedByProduct() as $mode)
		{
			$items[] = new list_Item($mode->getLabel(), $mode->getId());
		}
		return $items;
	}
	
	/**
	 * @return string
	 */
	public final function getDefaultId()
	{
		return 'free';
	}
}