<?php
/**
 * @package modules.catalog
 * @method catalog_ShopfolderService getInstance()
 */
class catalog_ShopfolderService extends generic_FolderService
{
	/**
	 * @return catalog_persistentdocument_shopfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/shopfolder');
	}

	/**
	 * Create a query based on 'modules_catalog/shopfolder' model.
	 * Return document that are instance of modules_catalog/shopfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/shopfolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/shopfolder' model.
	 * Only documents that are strictly instance of modules_catalog/shopfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/shopfolder', false);
	}
	
	/**
	 * @return catalog_persistentdocument_shopfolder
	 */
	public function getShopFolder()
	{
		// There should be only one shopfolder.
		return $this->createQuery()->findUnique();
	}
}