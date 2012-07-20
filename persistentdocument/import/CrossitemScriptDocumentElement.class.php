<?php
/**
 * catalog_CrossitemScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_CrossitemScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_crossitem
     */
    protected function initPersistentDocument()
    {
    	return catalog_CrossitemService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/crossitem');
	}
}