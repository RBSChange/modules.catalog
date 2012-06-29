<?php
class catalog_SaveDeclinationsListAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$product = $this->getDelinedProduct($request);	
		$declinationIds = $request->getParameter('declinationIds');
		$declinations = array();
		
		foreach ($declinationIds as $declinationId) 
		{
			$declinations[] = DocumentHelper::getDocumentInstance($declinationId, 'modules_catalog/productdeclination');
		}		
		$product->getDocumentService()->updateDeclinations($product, $declinations);
		$this->logAction($product);
		
		$data = $product->getDocumentService()->getDeclinationsInfos($product);
		return $this->sendJSON($data);
	}
	
	/**
	 * @param change_Request $request
	 * @return catalog_persistentdocument_declinedproduct
	 */
	private function getDelinedProduct($request)
	{
		return DocumentHelper::getCorrection($this->getDocumentInstanceFromRequest($request));
	}
}