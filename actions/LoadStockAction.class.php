<?php
class catalog_LoadStockAction extends generic_LoadJSONAction
{
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string[]
	 * @return Array
	 */
	protected function exportFieldsData($document, $allowedProperties)
	{
		$data = uixul_DocumentEditorService::getInstance()->exportFieldsData($document, $allowedProperties);
		catalog_StockService::getInstance()->loadStockInfo($document, $allowedProperties, $data);
		return $data;
	}
	
	/**
	 * @return boolean
	 */
	protected function isDocumentAction()
	{
		return true;
	}
}