<?php
/**
 * catalog_TaxScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_TaxScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_tax
     */
    protected function initPersistentDocument()
    {
    	return catalog_TaxService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/tax');
	}
}