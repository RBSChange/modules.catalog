<?php
/**
 * catalog_GetProdcutsToSortAction
 * @package modules.catalog.actions
 */
class catalog_GetProductsToSortAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$shelf = DocumentHelper::getDocumentInstance($request->getParameter('shelfId'), 'modules_catalog/shelf');
		$shop = DocumentHelper::getDocumentInstance($request->getParameter('shopId'), 'modules_catalog/shop');

		$result['nodes'] = catalog_CompiledproductService::getInstance()->getOrderInfosByShelfAndShop($shelf, $shop);
		
		return $this->sendJSON($result);
	}
}