<?php
/**
 * catalog_KititemScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_KititemScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_kititem
     */
    protected function initPersistentDocument()
    {
    	return catalog_KititemService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/kititem');
	}
}