<?php
/**
 * catalog_RoundPriceCommonStrategy
 * @package modules.catalog
 */
class catalog_RoundPriceCommonStrategy extends catalog_RoundPriceStrategy
{
	/**
	 * Singleton
	 * @var catalog_RoundPiceCommonStrategy
	 */
	private static $instance = null;

	/**
	 * @return catalog_RoundPiceCommonStrategy
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
		return 'Common price rounding.';
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return 'Two successive rounding with three then two digit after the point.';
	}
	
	/**
	 * @param Double $value
	 * @return Double
	 */
	public function roundPrice($value)
	{
		return round(round($value, 3), 2);
	}
}