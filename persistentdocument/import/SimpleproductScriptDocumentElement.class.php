<?php
/**
 * catalog_SimpleproductScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_SimpleproductScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_simpleproduct
     */
    protected function initPersistentDocument()
    {
    	$product = catalog_SimpleproductService::getInstance()->getNewDocumentInstance();
    	return $product;
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/simpleproduct');
	}
}