<?php
/**
 * catalog_CurrencyService
 * @package modules.catalog
 */
class catalog_CurrencyService extends f_persistentdocument_DocumentService
{
	/**
	 * @var catalog_CurrencyService
	 */
	private static $instance;
	
	/**
	 * @return catalog_CurrencyService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @return catalog_persistentdocument_currency
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/currency');
	}
	
	/**
	 * Create a query based on 'modules_catalog/currency' model.
	 * Return document that are instance of modules_catalog/currency,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/currency');
	}
	
	/**
	 * Create a query based on 'modules_catalog/currency' model.
	 * Only documents that are strictly instance of modules_catalog/currency
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/currency', false);
	}
	
	private $currencySymbols;
	
	/**
	 * @return String[]
	 */
	public function getCurrencySymbolsArray()
	{
		if ($this->currencySymbols === null)
		{
			$this->currencySymbols = array();
			$lines = catalog_CurrencyService::getInstance()->createQuery()->add(Restrictions::published())->setProjection(Projections::property('code', 'code'), Projections::property('symbol', 'symbol'))->find();
			foreach ( $lines as $line )
			{
				$this->currencySymbols[$line['code']] = $line['symbol'];
			}
		}
		return $this->currencySymbols;
	}
	
	/**
	 * 
	 */
	public function getCodeById($id)
	{
		$codes = $this->getCurrencyCodesArray();
		return isset($codes[$id]) ? $codes[$id] : null;
	}

	private $currencyCodes;
	
	protected function getCurrencyCodesArray()
	{
		if ($this->currencyCodes === null)
		{
			$this->currencyCodes = array();
			$lines = catalog_CurrencyService::getInstance()->createQuery()->add(Restrictions::published())->setProjection(Projections::property('code', 'code'), Projections::property('id', 'id'))->find();
			foreach ( $lines as $line )
			{
				$this->currencyCodes[$line['id']] = $line['code'];
			}
		}
		return $this->currencyCodes;
	}
	
	/**
	 * @param string $code
	 * @return catalog_persistentdocument_currency
	 */
	public function getByCode($code)
	{
		return catalog_CurrencyService::getInstance()->createQuery()->add(Restrictions::eq('code', $code))->findUnique();
	}
}