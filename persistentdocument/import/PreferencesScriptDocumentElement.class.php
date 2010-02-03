<?php
class catalog_PreferencesScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return catalog_persistentdocument_preferences
     */
    protected function initPersistentDocument()
    {
    	$document = ModuleService::getInstance()->getPreferencesDocument('catalog');
    	return ($document !== null) ? $document : catalog_PreferencesService::getInstance()->getNewDocumentInstance();
    }
}