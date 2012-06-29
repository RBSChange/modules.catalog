<?php
/**
 * catalog_ComparablepropertyfolderScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_ComparablepropertyfolderScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_comparablepropertyfolder
	 */
	protected function initPersistentDocument()
	{
		return catalog_ComparablepropertyfolderService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/comparablepropertyfolder');
	}
}