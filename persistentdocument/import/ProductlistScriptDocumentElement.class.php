<?php
class catalog_ProductlistScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_productlist
	 */
	protected function initPersistentDocument()
	{
		return catalog_ProductlistService::getInstance()->getNewDocumentInstance();
	}
}