<?php
/**
 * catalog_ShelfScriptDocumentElement
 * @package modules.catalog
 */
class catalog_ShelfScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function initPersistentDocument()
	{
		$document = catalog_ShelfService::getInstance()->getNewDocumentInstance();
		return $document;
	}
	
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/shelf');
	}
}