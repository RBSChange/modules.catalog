<?php
/**
 * catalog_CompileDeclinedProductAction
 * @package modules.catalog.actions
 */
class catalog_CompileDeclinedProductAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		$declinedProduct = $this->getProduct($request);
		$declinations = catalog_ProductdeclinationService::getInstance()->getArrayByDeclinedProduct($declinedProduct);
		foreach ($declinations as $declination) 
		{
			catalog_CompiledproductService::getInstance()->generateForProduct($declination);
		}
		$result['id'] = $declinedProduct->getId();
		$result['compiled'] = true;		
		$this->logAction($declinedProduct);
		return $this->sendJSON($result);
	}
	
	/**
	 * @param change_Request $request
	 * @return catalog_persistentdocument_declinedproduct
	 */	
	private function getProduct($request)
	{
		$product = $this->getDocumentInstanceFromRequest($request);
		if ($product instanceof catalog_persistentdocument_declinedproduct) 
		{
			return $product;
		}
		throw new BaseException('Invalid product', 'modules.catalog.errors.CompileProductAction.Invalid-product');
	}
	
	/**
	 * @see f_action_BaseJSONAction::getActionName()
	 */
	protected function getActionName()
	{
		return 'CompileProduct';
	}
}