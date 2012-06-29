<?php
/**
 * catalog_CurrencyScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_CurrencyScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_currency
	 */
	protected function initPersistentDocument()
	{
		return catalog_CurrencyService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/currency');
	}
}