<?php
/**
 * catalog_CompiledcrossitemScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_CompiledcrossitemScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_compiledcrossitem
     */
    protected function initPersistentDocument()
    {
    	return catalog_CompiledcrossitemService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/compiledcrossitem');
	}
}