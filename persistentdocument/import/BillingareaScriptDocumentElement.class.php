<?php
/**
 * catalog_BillingareaScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_BillingareaScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_billingarea
     */
    protected function initPersistentDocument()
    {
    	return catalog_BillingareaService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/billingarea');
	}
}