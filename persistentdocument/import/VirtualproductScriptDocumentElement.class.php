<?php
/**
 * catalog_VirtualproductScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_VirtualproductScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_virtualproduct
     */
    protected function initPersistentDocument()
    {
    	return catalog_VirtualproductService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/virtualproduct');
	}
}