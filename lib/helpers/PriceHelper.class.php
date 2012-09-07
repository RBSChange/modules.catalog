<?php
/**
 * @deprecated
 */
class catalog_PriceHelper
{	
	const CURRENCY_POSITION_LEFT = 'left';
	const CURRENCY_POSITION_RIGHT = 'right';

	/**
	 * @deprecated
	 */	
	public static function getTaxRateByValue($price, $priceWithoutTaxe)
	{
		Framework::deprecated('Removed in 4.0');
		return catalog_TaxService::getInstance()->getTaxRateByValue($price, $priceWithoutTaxe);
	}

	/**
	 * @deprecated
	 */
	public static function getCurrencySymbol($code)
	{
		Framework::deprecated('Removed in 4.0');
		$cs = catalog_CurrencyService::getInstance()->getCurrencySymbolsArray();
		if (!isset($cs[$code]))
		{
			throw new catalog_Exception($code . " does not relate to an existing curreny code");
		}
		return $cs[$code];
	}
	
	
	/**
	 * @deprecated
	 */
	public static function addTax($value, $taxCode)
	{
		Framework::deprecated('Removed in 4.0');
		return ($value * (1 + self::getTaxRateByCode($taxCode)));
	}

	/**
	 * @deprecated
	 */
	public static function removeTax($value, $taxCode)
	{
		Framework::deprecated('Removed in 4.0');
		return ($value / (1 + self::getTaxRateByCode($taxCode)));
	}
	
	/**
	 * @deprecated
	 */
	public static function formatTaxRate($taxRate)
	{
		Framework::deprecated('Removed in 4.0');
		return (round($taxRate * 100, 2)) . "%";
	}

	
	/**
	 * @deprecated
	 */
	public static function applyFormat($priceValue, $format)
	{
		Framework::deprecated('Removed in 4.0');
		$priceValue = catalog_PriceFormatter::getInstance()->round($priceValue);
		return sprintf($format, number_format($priceValue, 2, ',', ' '));
	}
	
	/**
	 * @deprecated
	 */
	public static function roundPrice($value)
	{
		Framework::deprecated('Removed in 4.0');
		return catalog_PriceFormatter::getInstance()->round($value);
	}
	
	/**
	 * @deprecated
	 */
	public static function getTaxRateByCode($code)
	{
		Framework::deprecated('Removed in 4.0');
		return catalog_TaxService::getInstance()->getTaxRateByCode($code);
	}
		
	
	/**
	 * @deprecated
	 */
	public static final function setTaxRateResolverStrategy($strategy)
	{
		Framework::deprecated('Removed in 4.0');
	}
}