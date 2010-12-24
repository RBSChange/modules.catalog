<?php
/**
 * catalog_DefaultTaxRateResolverStrategy
 * @package modules.catalog
 */
class catalog_DefaultTaxRateResolverStrategy extends catalog_TaxRateResolverStrategy
{
	private $taxRates = null;

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
		if ($this->taxRates === null)
		{
			$this->taxRates = array();
			foreach (catalog_TaxService::getInstance()->createQuery()->setProjection(Projections::property('code', 'code'), Projections::property('rate', 'rate'))->find() as $line)
			{
				 $this->taxRates[$line['code']] = $line['rate'];
			}
		}
		if (!isset($this->taxRates[$code]))
		{
			throw new catalog_Exception('The tax code "' . $code . '" is not defined.');
		}
		return $this->taxRates[$code];
	}
}