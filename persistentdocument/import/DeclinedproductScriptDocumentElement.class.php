<?php
/**
 * catalog_DeclinedproductScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_DeclinedproductScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_declinedproduct
     */
    protected function initPersistentDocument()
    {
    	$product = catalog_DeclinedproductService::getInstance()->getNewDocumentInstance();
    	return $product;
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/declinedproduct');
	}
}