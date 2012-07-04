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
		$lang = $request->getParameter('lang');
		$applyToAllLangs = $request->hasParameter('applyToAllLangs') && $request->getParameter('applyToAllLangs') == 'true';
		
		$cps = catalog_CompiledproductService::getInstance();		
		if ($request->hasParameter('co'))
		{
			$productOrder = array_flip($request->getParameter('co'));
			$cps->setPositionsByShelfAndShopAndLang($shelf, $shop, $lang, $productOrder, $applyToAllLangs);
		}

		$result['nodes'] = catalog_CompiledproductService::getInstance()->getOrderInfosByShelfAndShopAndLang($shelf, $shop, $lang);
		return $this->sendJSON($result);
	}
}