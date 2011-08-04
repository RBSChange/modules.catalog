<?php
class catalog_UpdateStockAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$document = $this->getDocumentInstanceFromRequest($request);
		$propertiesNames = explode(',', $request->getParameter('documentproperties', ''));		
		$propertiesValue = array();
		foreach ($propertiesNames as $propertyName)
		{
			if (f_util_StringUtils::isEmpty($propertyName)) {continue;}
			if ($request->hasParameter($propertyName))
			{
				$propertiesValue[$propertyName] = $request->getParameter($propertyName);
			}			
		}
		uixul_DocumentEditorService::getInstance()->importFieldsData($document, $propertiesValue);	
		catalog_StockService::getInstance()->updateStockInfo($document, $propertiesValue);
		
		$propertiesNames[] = 'id';
		$propertiesNames[] = 'lang';
		$data = $this->exportFieldsData($document, $propertiesNames);
		return $this->sendJSON($data);
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param String[]
	 * @return Array
	 */
	protected function exportFieldsData($document, $allowedProperties)
	{
		$data = uixul_DocumentEditorService::getInstance()->exportFieldsData($document, $allowedProperties);
		catalog_StockService::getInstance()->loadStockInfo($document, $allowedProperties, $data);
		return $data;
	}
	
	/**
	 * @return Boolean
	 */
	protected function isDocumentAction()
	{
		return true;
	}
}