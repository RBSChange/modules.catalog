<?php
/**
 * catalog_SaveProductOrderAction
 * @package modules.catalog.actions
 */
class catalog_SaveProductOrderAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		
		if (!$request->hasParameter('co'))
		{
			return $this->sendJSONError(f_Locale::translate("&modules.generic.backoffice.OrderChildrenInvalidParametersErrorMessage;"));
		}
		$productOrder = array_flip($request->getParameter('co'));
		
		$shelf = DocumentHelper::getDocumentInstance($request->getParameter('shelfId'), 'modules_catalog/shelf');
		$shop = DocumentHelper::getDocumentInstance($request->getParameter('shopId'), 'modules_catalog/shop');
		
		$cps = catalog_CompiledproductService::getInstance();
		$cps->setPositionsByShelfAndShop($shelf, $shop, $productOrder);
		
		$result['nodes'] = catalog_CompiledproductService::getInstance()->getOrderInfosByShelfAndShop($shelf, $shop);
		
		return $this->sendJSON($result);
	}
}