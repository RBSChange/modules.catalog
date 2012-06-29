<?php
/**
 * @package modules.catalog
 * @method catalog_ProductqueryfolderService getInstance()
 */
class catalog_ProductqueryfolderService extends filter_QueryfolderService
{
	/**
	 * @return catalog_persistentdocument_productqueryfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/productqueryfolder');
	}

	/**
	 * Create a query based on 'modules_catalog/productqueryfolder' model.
	 * Return document that are instance of modules_catalog/productqueryfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/productqueryfolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/productqueryfolder' model.
	 * Only documents that are strictly instance of modules_catalog/productqueryfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/productqueryfolder', false);
	}
}