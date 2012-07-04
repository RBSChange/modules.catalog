<?php
/**
 * catalog_ListAvailablelangsforshopService
 * @package modules.catalog.lib.services
 */
class catalog_ListAvailablelangsforshopService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array();
		$shop = null;
		if (isset($this->parameters['shopId']))
		{
			$shopId = $this->parameters['shopId'];
			$shop = catalog_persistentdocument_shop::getInstanceById($shopId);
		}
		
		if ($shop !== null)
		{
			$ls = LocaleService::getInstance();
			foreach ($shop->getI18nInfo()->getLangs() as $lang)
			{
				$items[] = new list_Item($ls->trans('m.uixul.bo.languages.' . $lang, array('ucf')), $lang);
			}
		}
		return $items;
	}

	/**
	 * @var array
	 */
	private $parameters = array();
	
	/**
	 * @see list_persistentdocument_dynamiclist::getListService()
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
	
	/**
	 * @see list_persistentdocument_dynamiclist::getItemByValue()
	 * @param string $value;
	 * @return list_Item
	 */
	public function getItemByValue($value)
	{
		return new list_Item(LocaleService::getInstance()->trans('m.uixul.bo.languages.' . $value, array('ucf')), $value);
	}
}