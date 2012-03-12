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
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return catalog_TaxService::getInstance()->getTaxRateByValue($price, $priceWithoutTaxe);
	}

	/**
	 * @deprecated
	 */
	public static function getCurrencySymbol($code)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
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
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return ($value * (1 + self::getTaxRateByCode($taxCode)));
	}

	/**
	 * @deprecated
	 */
	public static function removeTax($value, $taxCode)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return ($value / (1 + self::getTaxRateByCode($taxCode)));
	}
	
	/**
	 * @deprecated
	 */
	public static function formatTaxRate($taxRate)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return (round($taxRate * 100, 2)) . "%";
	}

	
	/**
	 * @deprecated
	 */
	public static function applyFormat($priceValue, $format)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		$priceValue = catalog_PriceFormatter::getInstance()->round($priceValue);
		return sprintf($format, number_format($priceValue, 2, ',', ' '));
	}
	
	/**
	 * @deprecated
	 */
	public static function roundPrice($value)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return catalog_PriceFormatter::getInstance()->round($value);
	}
	
	/**
	 * @deprecated
	 */
	public static function getTaxRateByCode($code)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
		return catalog_TaxService::getInstance()->getTaxRateByCode($code);
	}
		
	
	/**
	 * @deprecated
	 */
	public static final function setTaxRateResolverStrategy($strategy)
	{
		if (Framework::inDevelopmentMode())
		{
			Framework::fatal(f_util_ProcessUtils::getBackTrace());
		}
		else
		{
			Framework::warn('DEPRECATED Call to: ' . __METHOD__);
		}
	}
}