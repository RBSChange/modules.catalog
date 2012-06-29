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
	 * @param string $stockLevel
	 * @return string
	 */
	public function getAvailability($stockLevel)
	{
		switch ($stockLevel)
		{
			case catalog_StockService::LEVEL_AVAILABLE : 
				$return = LocaleService::getInstance()->trans('m.catalog.frontoffice.level-available', array('ucf', 'html'));
				break;
			
			case catalog_StockService::LEVEL_FEW_IN_STOCK : 
				$return = LocaleService::getInstance()->trans('m.catalog.frontoffice.level-few-in-stock', array('ucf', 'html'));
				break;
				
			case catalog_StockService::LEVEL_UNAVAILABLE : 
				$return = LocaleService::getInstance()->trans('m.catalog.frontoffice.level-unavailable', array('ucf', 'html'));
				break;
			
			default :
				$return = null;
				break;
		}
		return $return;
	}
	
	// Deprecated.
	
	/**
	 * @deprecated
	 */
	public function getLabel()
	{
		return 'Default availability management.';
	}
	
	/**
	 * @deprecated
	 */
	public function getDescription()
	{
		return 'Default availability management.';
	}
}