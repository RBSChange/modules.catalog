<?php
/**
 * catalog_TopshelfScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_TopshelfScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_topshelf
	 */
	protected function initPersistentDocument()
	{
		return catalog_TopshelfService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/topshelf');
	}
}