<?php
/**
 * catalog_CompileProductAction
 * @package modules.catalog.actions
 */
class catalog_CompileProductAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$product = $this->getProduct($request);
		catalog_CompiledproductService::getInstance()->generateForProduct($product);
	
		$result['id'] = $product->getId();
		$result['compiled'] = $product->getCompiled();
		
		$this->logAction($product);
		return $this->sendJSON($result);
	}
	
	/**
	 * @param change_Request $request
	 * @return catalog_persistentdocument_product
	 */	
	private function getProduct($request)
	{
		$product = $this->getDocumentInstanceFromRequest($request);
		if ($product instanceof catalog_persistentdocument_product) 
		{
			return $product;
		}
		throw new BaseException('Invalid product', 'modules.catalog.errors.compileproductAction.Invalid-product');
	}
}