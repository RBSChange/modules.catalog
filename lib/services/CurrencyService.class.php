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
		return $this->getPersistentProvider()->createQuery('modules_catalog/currency');
	}
	
	/**
	 * Create a query based on 'modules_catalog/currency' model.
	 * Only documents that are strictly instance of modules_catalog/currency
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/currency', false);
	}
	
	/**
	 * @param string $code
	 * @return catalog_persistentdocument_currency
	 */
	public function getByCode($code)
	{
		return $this->createQuery()->add(Restrictions::eq('code', $code))->findUnique();
	}
	
	
	/**
	 * @return array<srtring => string>
	 */
	public function getCurrencySymbolsArray()
	{
		$result = array();
		$lines = $this->createQuery()->add(Restrictions::published())
			->setProjection(Projections::property('code', 'code'), Projections::property('symbol', 'symbol'))
			->find();
		foreach ($lines as $line )
		{
			$result[$line['code']] = $line['symbol'];
		}
		return $result;
	}
	
	/**
	 * @return string|null
	 */
	public function getSymbolByCode($code)
	{
		$currency = $this->getByCode($code);
		return $currency ? $currency->getSymbol() : null;
	}
	
	/**
	 * @param integer $id
	 * @return string|NULL
	 */
	public function getCodeById($id)
	{
		$currency = DocumentHelper::getDocumentInstanceIfExists($id);
		return ($currency instanceof catalog_persistentdocument_currency) ? $currency->getCode() : null;
	}
}