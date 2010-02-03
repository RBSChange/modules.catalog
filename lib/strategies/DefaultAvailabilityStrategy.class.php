<?php
/**
 * catalog_DefaultAvailabilityStrategy
 * @package modules.catalog
 */
class catalog_DefaultAvailabilityStrategy extends catalog_AvailabilityStrategy
{
	/**
	 * Singleton
	 * @var catalog_DefaultAvailabilityStrategy
	 */
	private static $instance = null;

	/**
	 * @return catalog_DefaultAvailabilityStrategy
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
	 * @return String
	 */
	public function getLabel()
	{
		return 'Default availability management.';
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return 'Default availability management.';
	}

	/**
	 * @param String $stockLevel
	 * @return String
	 */
	public function getAvailability($stockLevel)
	{
		switch ($stockLevel)
		{
			case catalog_StockService::LEVEL_AVAILABLE : 
				$return = f_Locale::translate('&modules.catalog.frontoffice.Level-available;');
				break;
			
			case catalog_StockService::LEVEL_FEW_IN_STOCK : 
				$return = f_Locale::translate('&modules.catalog.frontoffice.Level-few-in-stock;');
				break;
				
			case catalog_StockService::LEVEL_UNAVAILABLE : 
				$return = f_Locale::translate('&modules.catalog.frontoffice.Level-unavailable;');
				break;
			
			default :
				$return = null;
				break;
		}
		return $return;
	}
}