<?php
/**
 * @package modules.catalog
 * @method catalog_PreferencesService getInstance()
 */
class catalog_PreferencesService extends f_persistentdocument_DocumentService
{
	/**
	 * @return catalog_persistentdocument_preferences
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/preferences');
	}

	/**
	 * Create a query based on 'modules_catalog/preferences' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/preferences');
	}
}