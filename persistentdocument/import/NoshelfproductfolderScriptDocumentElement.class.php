<?php
/**
 * catalog_NoshelfproductfolderScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_NoshelfproductfolderScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_noshelfproductfolder
     */
    protected function initPersistentDocument()
    {
    	return catalog_NoshelfproductfolderService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/noshelfproductfolder');
	}
}