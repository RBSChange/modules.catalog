<?php
/**
 * catalog_RoundPriceStrategy
 * @package modules.catalog
 */
abstract class catalog_RoundPriceStrategy extends BaseService
{
	/**
	 * @var catalog_RoundPiceStrategy
	 */
	private static $strategy = null;

	/**
	 * @param catalog_RoundPiceStrategy $strategy
	 */
	public final static function setStrategy($strategy)
	{
		self::$strategy = $strategy;
	}

	/**
	 * @return catalog_RoundPiceStrategy
	 */
	public final static function getStrategy()
	{
		if (is_null(self::$strategy))
		{
			$className = Framework::getConfiguration('modules/catalog/roundPriceStrategyClass', false);
			if ($className === false)
			{
				// No strategy defined in the project's config file: use default one.
				$className = 'catalog_RoundPriceDefaultStrategy';
				if (Framework::isDebugEnabled())
				{
					Framework::debug("No strategy defined to round prices for this projet: using default one (".$className.").");
				}
			}
			self::$strategy = f_util_ClassUtils::callMethodByName($className . '::getInstance');
		}
		return self::$strategy;
	}
	
	/**
	 * @return String
	 */
	public abstract function getLabel();

	/**
	 * @return String
	 */
	public abstract function getDescription();

	/**
	 * @param Double $value
	 * @return Double
	 */
	public abstract function roundPrice($value);
}