<?php
/**
 * catalog_ListAvailablelangsforshopService
 * @package modules.catalog.lib.services
 */
class catalog_ListAvailablelangsforshopService extends BaseService implements list_ListItemsService
{
	/**
	 * @var catalog_ListAvailablelangsforshopService
	 */
	private static $instance;

	/**
	 * @return catalog_ListAvailablelangsforshopService
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
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public function getItems()
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
				$items[] = new list_Item($ls->transBO('m.uixul.bo.languages.' . $lang, array('ucf')), $lang);
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
		return new list_Item(LocaleService::getInstance()->transBO('m.uixul.bo.languages.' . $value, array('ucf')), $value);
	}
}