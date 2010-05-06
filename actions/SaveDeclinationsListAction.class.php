<?php
class catalog_SaveDeclinationsListAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
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
	 * @param Request $request
	 * @return catalog_persistentdocument_declinedproduct
	 */
	private function getDelinedProduct($request)
	{
		return DocumentHelper::getCorrection($this->getDocumentInstanceFromRequest($request));
	}
}