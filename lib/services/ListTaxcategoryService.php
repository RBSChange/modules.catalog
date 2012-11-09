<?php
/**
 * @deprecated
 */
class catalog_ListTaxcategoryService extends change_BaseService implements list_ListItemsService
{
	/**
 * @deprecated
 */
	public final function getItems()
	{
		$items = array();
		
		// Generate the items here.
		
		return $items;
	}

	/**
 * @deprecated
 */
	private $parameters = array();
	
	/**
 * @deprecated
 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
}