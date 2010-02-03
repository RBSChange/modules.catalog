<?php
/**
 * catalog_AttributefolderScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_AttributefolderScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_attributefolder
     */
    protected function initPersistentDocument()
    {
    	$folder = catalog_AttributefolderService::getInstance()->getAttributeFolder();
    	if ($folder === null)
    	{
    		$folder = catalog_AttributefolderService::getInstance()->getNewDocumentInstance();
    	}
    	return $folder;
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/attributefolder');
	}
}