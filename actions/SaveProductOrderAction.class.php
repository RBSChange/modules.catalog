<?php
/**
 * catalog_SaveProductOrderAction
 * @package modules.catalog.actions
 */
class catalog_SaveProductOrderAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		$shelf = DocumentHelper::getDocumentInstance($request->getParameter('shelfId'), 'modules_catalog/shelf');
		$shop = DocumentHelper::getDocumentInstance($request->getParameter('shopId'), 'modules_catalog/shop');
		$cps = catalog_CompiledproductService::getInstance();
		
		if ($request->hasParameter('co'))
		{
			$productOrder = array_flip($request->getParameter('co'));
			$cps->setPositionsByShelfAndShop($shelf, $shop, $productOrder);
		}

		$result['nodes'] = catalog_CompiledproductService::getInstance()->getOrderInfosByShelfAndShop($shelf, $shop);
		return $this->sendJSON($result);
	}
}