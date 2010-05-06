<?php
/**
 * catalog_BundleproductScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_BundleproductScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_bundleproduct
     */
    protected function initPersistentDocument()
    {
    	return catalog_BundleproductService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/bundleproduct');
	}
}