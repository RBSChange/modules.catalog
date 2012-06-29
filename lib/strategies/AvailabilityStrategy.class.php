<?php
/**
 * catalog_AvailabilityStrategy
 * @package modules.catalog
 */
abstract class catalog_AvailabilityStrategy extends change_BaseService
{
	/**
	 * @var catalog_AvailabilityStrategy
	 */
	private static $strategy = null;

	/**
	 * @param catalog_AvailabilityStrategy $strategy
	 */
	public final static function setStrategy($strategy)
	{
		self::$strategy = $strategy;
	}

	/**
	 * @return catalog_AvailabilityStrategy
	 */
	public final static function getStrategy()
	{
		if (is_null(self::$strategy))
		{
			$className = Framework::getConfiguration('modules/catalog/availabilityStrategyClass', false);
			if ($className === false)
			{
				// No strategy defined in the project's config file: use default one.
				$className = 'catalog_DefaultAvailabilityStrategy';
				if (Framework::isDebugEnabled())
				{
					Framework::debug("No strategy defined to manage product availability for this projet: using default one (".$className.").");
				}
			}
			self::$strategy = f_util_ClassUtils::callMethodByName($className . '::getInstance');
		}
		return self::$strategy;
	}

	/**
	 * @param string $stockLevel
	 * @return string
	 */
	public abstract function getAvailability($stockLevel);
	
	// Deprecated
	
	/**
	 * @deprecated
	 */
//	public abstract function getLabel();
	
	/**
	 * @deprecated
	 */
//	public abstract function getDescription();
}