<?php
/**
 * @package modules.catalog.persistentdocument.import
 */
class catalog_ProductqueryfolderScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_productqueryfolder
     */
    protected function initPersistentDocument()
    {
    	return catalog_ProductqueryfolderService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/productqueryfolder');
	}
}