<?php
/**
 * catalog_ComparablepropertyfolderService
 * @package modules.catalog
 */
class catalog_ComparablepropertyfolderService extends generic_FolderService
{
	/**
	 * @var catalog_ComparablepropertyfolderService
	 */
	private static $instance;

	/**
	 * @return catalog_ComparablepropertyfolderService
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
	 * @return catalog_persistentdocument_comparablepropertyfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/comparablepropertyfolder');
	}

	/**
	 * Create a query based on 'modules_catalog/comparablepropertyfolder' model.
	 * Return document that are instance of modules_catalog/comparablepropertyfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_catalog/comparablepropertyfolder');
	}
	
	/**
	 * Create a query based on 'modules_catalog/comparablepropertyfolder' model.
	 * Only documents that are strictly instance of modules_catalog/comparablepropertyfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_catalog/comparablepropertyfolder', false);
	}
}