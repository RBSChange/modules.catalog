<?php
/**
 * @package modules.catalog
 * @method catalog_ListTaxcategoryService getInstance()
 */
class catalog_ListTaxcategoryService extends change_BaseService implements list_ListItemsService
{
	/**
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