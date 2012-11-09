<?php
/**
 * @deprecated
 */
class catalog_ListTaxcategoryService extends BaseService
{
	private static $instance;
	
	/**
	 * @deprecated
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
	 * @deprecated
	 */
	public final function getItems()
	{
		$items = array();
		
		// Generate the items here.

		return $items;
	}
	
	private $parameters = array();
	
	/**
	 * @deprecated
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}

}