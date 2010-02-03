<?php
/**
 * catalog_DefaultTaxRateResolverStrategy
 * @package modules.catalog
 */
class catalog_DefaultTaxRateResolverStrategy extends catalog_TaxRateResolverStrategy
{
	private $taxRates = array('0' => 0, '1' => 0.196, '2' => 0.055, '3' => 0.085);

	/**
	 * @return String
	 */
	public function getLabel()
	{
		return 'Default tax rate resolver from a tax code to a tax rate.';
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return 'Default tax rate resolver from a tax code to a tax rate.';
	}

	/**
	 * @param String $code
	 * @return Double
	 * @throws catalog_Exception
	 */
	public function getTaxRateByCode($code)
	{
		if (!isset($this->taxRates[$code]))
		{
			throw new catalog_Exception('The tax code "' . $code . '" is not defined.');
		}
		return $this->taxRates[$code];
	}
}