<?php
/**
 * catalog_ShippingfilterScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_ShippingfilterScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_shippingfilter
     */
    protected function initPersistentDocument()
    {
    	return catalog_ShippingfilterService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/shippingfilter');
	}
}