<?php
/**
 * catalog_TaxRateResolverStrategy
 * @package modules.catalog
 */
abstract class catalog_TaxRateResolverStrategy
{
	/**
	 * @return String
	 */
	public abstract function getLabel();

	/**
	 * @return String
	 */
	public abstract function getDescription();

	/**
	 * @param String $code
	 * @return Double
	 */
	public abstract function getTaxRateByCode($code);
}