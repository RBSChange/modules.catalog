<?php
/**
 * catalog_KitScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_KitScriptDocumentElement extends catalog_ProductScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_kit
	 */
	protected function initPersistentDocument()
	{
		return catalog_KitService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/kit');
	}
}