<?php
/**
 * catalog_PriceHelper
 * @package modules.catalog
 */
class catalog_PriceHelper
{	
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
				self::$taxRateResolverStrategy = catalog_TaxService::getInstance();
			}
			else
			{
				self::$taxRateResolverStrategy = new $strategyClassName();
			}
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
	 * @param Double $taxRate
	 * @return String
	 */
	public static function formatTaxRate($taxRate)
	{
		return (round($taxRate * 100, 2)) . "%";
	}
			
	private static $currencySymbols = null;
	
	/**
	 * @param string $code
	 * @return String
	 * @throw catalog_Exception if the currencyCode does not related to an existing currency code
	 */	
	public static function getCurrencySymbol($code)
	{
		if (self::$currencySymbols === null)
		{
			self::$currencySymbols = catalog_CurrencyService::getInstance()->getCurrencySymbolsArray();
		}
		
		if (!isset(self::$currencySymbols[$code]))
		{
			throw new catalog_Exception($code . " does not relate to an existing curreny code");
		}
		return self::$currencySymbols[$code];
	}
	
	// Deprecated
	
	/**
	 * @param Double $priceValue
	 * @param String $format ex: "%s €"
	 * @return string
	 * @see getPriceFormat()
	 * @deprecated
	 */
	public static function applyFormat($priceValue, $format)
	{
		$priceValue = catalog_PriceFormatter::getInstance()->round($priceValue);
		return sprintf($format, number_format($priceValue, 2, ',', ' '));
	}
}