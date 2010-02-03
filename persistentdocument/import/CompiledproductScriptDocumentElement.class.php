<?php
/**
 * catalog_CompiledproductScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_CompiledproductScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_compiledproduct
     */
    protected function initPersistentDocument()
    {
    	return catalog_CompiledproductService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/compiledproduct');
	}
}