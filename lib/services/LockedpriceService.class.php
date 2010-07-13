<?php
/**
 * catalog_LockedpriceService
 * @package modules.catalog
 */
class catalog_LockedpriceService extends catalog_PriceService
{
	/**
	 * @var catalog_LockedpriceService
	 */
	private static $instance;

	/**
	 * @return catalog_LockedpriceService
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
	 * @return catalog_persistentdocument_lockedprice
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/lockedprice');
	}

	/**
	 * Create a query based on 'modules_catalog/lockedprice' model.
	 * Return document that are instance of modules_catalog/lockedprice,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/lockedprice');
	}
	
	/**
	 * Create a query based on 'modules_catalog/lockedprice' model.
	 * Only documents that are strictly instance of modules_catalog/lockedprice
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/lockedprice', false);
	}
}