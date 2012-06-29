<?php
/**
 * @package modules.catalog
 * @method catalog_ComparablepropertyService getInstance()
 */
class catalog_ComparablepropertyService extends f_persistentdocument_DocumentService
{
	/**
	 * @return catalog_persistentdocument_comparableproperty
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_catalog/comparableproperty');
	}

	/**
	 * Create a query based on 'modules_catalog/comparableproperty' model.
	 * Return document that are instance of modules_catalog/comparableproperty,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/comparableproperty');
	}
	
	/**
	 * Create a query based on 'modules_catalog/comparableproperty' model.
	 * Only documents that are strictly instance of modules_catalog/comparableproperty
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_catalog/comparableproperty', false);
	}

	/**
	 * @param catalog_persistentdocument_comparableproperty $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$resume = parent::getResume($document, $forModuleName, $allowedSections);
		
		$resume['properties']['classname'] = $document->getClassName();
		$resume['properties']['parameters'] = implode(', ', $document->getParameters());
		
		return $resume;
	}
}