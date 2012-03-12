<?php
/**
 * catalog_BillingareafolderScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_BillingareafolderScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_billingareafolder
     */
    protected function initPersistentDocument()
    {
    	return catalog_BillingareafolderService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/billingareafolder');
	}
}