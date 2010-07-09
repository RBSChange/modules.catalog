<?php
/**
 * catalog_PreferencesService
 * @package modules.catalog
 */
class catalog_PreferencesService extends f_persistentdocument_DocumentService
{
	/**
	 * Singleton
	 * @var catalog_PreferencesService
	 */
	private static $instance = null;

	/**
	 * @return catalog_PreferencesService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

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
		return $this->pp->createQuery('modules_catalog/preferences');
	}

	/**
	 * @param catalog_persistentdocument_preferences $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		$document->setLabel('&modules.catalog.bo.general.Module-name;');
	}
}