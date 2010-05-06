<?php
/**
 * catalog_BundleditemScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_BundleditemScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_bundleditem
     */
    protected function initPersistentDocument()
    {
    	return catalog_BundleditemService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/bundleditem');
	}
}