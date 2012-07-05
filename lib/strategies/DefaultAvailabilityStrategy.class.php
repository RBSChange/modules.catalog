<?php
/**
 * @package modules.catalog
 * @method catalog_DefaultAvailabilityStrategy getInstance()
 */
class catalog_DefaultAvailabilityStrategy extends catalog_AvailabilityStrategy
{	
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
}