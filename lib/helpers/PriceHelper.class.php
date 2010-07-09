<?php
/**
 * catalog_PriceHelper
 * @package modules.catalog
 */
class catalog_PriceHelper
{
	const MODE_B_TO_B = 'btob';
	const MODE_B_TO_C = 'btoc';
	
	const CURRENCY_POSITION_LEFT = 'left';
	const CURRENCY_POSITION_RIGHT = 'right';

	/**
	 * @var order_TaxRateResolverStrategy
	 */
	private static $taxRateResolverStrategy = null;

	/**
	 * @param String $code
	 * @return Double
	 */
	public static function getTaxRateByCode($code)
	{
		if (is_null(self::$taxRateResolverStrategy))
		{
			$strategyClassName = Framework::getConfiguration('modules/catalog/taxRateResolverStrategyClass', false);
			if ($strategyClassName === false)
			{
				$strategyClassName = 'catalog_DefaultTaxRateResolverStrategy';
			}
			self::$taxRateResolverStrategy = new $strategyClassName();
		}
		return self::$taxRateResolverStrategy->getTaxRateByCode($code);
	}
	
	public static function getTaxRateByValue($price, $priceWithoutTaxe)
	{
		if ($priceWithoutTaxe > 0)
		{
		 	return ($price / $priceWithoutTaxe) - 1;
		}
		return 0;
	}
	

	/**
	 * @param catalog_TaxRateResolverStrategy $strategy
	 */
	public static final function setTaxRateResolverStrategy($strategy)
	{
		self::$taxRateResolverStrategy = $strategy;
	}

	/**
	 * @param Double $value
	 * @param String $taxCode
	 * @return Double
	 * @throws catalog_Exception
	 */
	public static function addTax($value, $taxCode)
	{
		return ($value * (1 + self::getTaxRateByCode($taxCode)));
	}

	/**
	 * @param Double $value
	 * @param String $taxCode
	 * @return Double
	 * @throws catalog_Exception
	 */
	public static function removeTax($value, $taxCode)
	{
		return ($value / (1 + self::getTaxRateByCode($taxCode)));
	}

	/**
	 * If this is a btob project, add the tax, else return the value.
	 * @param Double $value
	 * @param String $taxCode
	 * @param catalog_persistentdocument_shop $shop
	 * @return Double
	 */
	public static function getValueWithTax($value, $taxCode, $shop = null)
	{
		if (self::getPriceMode($shop) == self::MODE_B_TO_B)
		{
			return self::addTax($value, $taxCode);
		}
		else
		{
			return $value;
		}
	}

	/**
	 * If this is a btoc project, remove the tax, else return the value.
	 * @param Double $value
	 * @param String $taxCode
	 * @param catalog_persistentdocument_shop $shop
	 * @return Double
	 */
	public static function getValueWithoutTax($value, $taxCode, $shop = null)
	{
		if (self::getPriceMode($shop) == self::MODE_B_TO_C)
		{
			return self::removeTax($value, $taxCode);
		}
		else
		{
			return $value;
		}
	}
	
	/**
	 * @param Double $taxRate
	 * @return String
	 */
	public static function formatTaxRate($taxRate)
	{
		return ($taxRate * 100) . "%";
	}
	
	/**
	 * Calls the selected RoundPriceStrategy.
	 * @param Double $value
	 * @return Double
	 */
	public static function roundPrice($value)
	{
		$strategy = catalog_RoundPriceStrategy::getStrategy();
		return $strategy->roundPrice($value);
	}
	
	/**
	 * @param Double $priceValue
	 * @param String $format ex: "%s €"
	 * @return string
	 * @see getPriceFormat()
	 */
	public static function applyFormat($priceValue, $format)
	{
		$priceValue = self::roundPrice($priceValue);
		return sprintf($format, number_format($priceValue, 2, ',', ' '));
	}
	
	/**
	 * @var String
	 */
	private static $priceMode = null;
	
	/**
	 * @return String
	 */
	public static function getDefaultPriceMode()
	{
		if (self::$priceMode === null)
		{
			self::$priceMode = Framework::getConfiguration('modules/catalog/price-mode', false);
			// If there is no defined price mode, set it to "btoc".
			if (self::$priceMode === false)
			{
				self::$priceMode = catalog_PriceHelper::MODE_B_TO_C;
			}
		}
		return self::$priceMode;
	}
	
	/**
	 * @param catalog_persistentdocument_shop $shop
	 * @return String
	 */
	public static function getPriceMode($shop = null)
	{
		return $shop === null ? self::getDefaultPriceMode() : $shop->getPriceMode();
	}
}