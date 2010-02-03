<?php
/**
 * catalog_ProductdeclinationScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_ProductdeclinationScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_productdeclination
     */
    protected function initPersistentDocument()
    {
    	return catalog_ProductdeclinationService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/productdeclination');
	}
}