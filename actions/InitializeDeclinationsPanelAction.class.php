<?php
class catalog_InitializeDeclinationsPanelAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$product = $this->getDelinedProduct($request);	
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