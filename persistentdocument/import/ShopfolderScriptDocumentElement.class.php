<?php
/**
 * catalog_ShopfolderScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_ShopfolderScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_shopfolder
	 */
	protected function initPersistentDocument()
	{
		return catalog_ShopfolderService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/shopfolder');
	}
}