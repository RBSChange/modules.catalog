<?php
/**
 * catalog_ListTaxcategoryService
 * @package modules.catalog.lib.services
 */
class catalog_ListTaxcategoryService extends BaseService
{
	/**
	 * @var catalog_ListTaxcategoryService
	 */
	private static $instance;

	/**
	 * @return catalog_ListTaxcategoryService
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
	public final function getItems()
	{
		$items = array();
		
		// Generate the items here.
		
		return $items;
	}

	/**
	 * @var Array
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
	
}