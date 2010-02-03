<?php
/**
 * catalog_ProductScriptDocumentElement
 * @package modules.catalog
 */
class catalog_ProductScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function initPersistentDocument()
	{
		throw new Exception('Can\'t create directly a product, use descendant models (simpleproduct, declinedproduct...');
	}
}