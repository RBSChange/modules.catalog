<?php
class catalog_InitializeDeclinationsPanelAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$product = $this->getDelinedProduct($request);	
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