<?php
/**
 * catalog_RoundPriceDefaultStrategy
 * @package modules.catalog
 */
class catalog_RoundPriceDefaultStrategy extends catalog_RoundPriceStrategy
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
		return 'Default price rounding.';
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return 'Prices are rounded with two digits after the point.';
	}
	
	/**
	 * @param Double $value
	 * @return Double
	 */
	public function roundPrice($value)
	{
		return round($value, 2);
	}
}