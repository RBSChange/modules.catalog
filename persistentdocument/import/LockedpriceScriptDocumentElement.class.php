<?php
/**
 * catalog_LockedpriceScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_LockedpriceScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_lockedprice
	 */
	protected function initPersistentDocument()
	{
		return catalog_LockedpriceService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/lockedprice');
	}
}