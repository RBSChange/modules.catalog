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
		$lang = $request->getParameter('lang');
		$applyToAllLangs = $request->hasParameter('applyToAllLangs') && $request->getParameter('applyToAllLangs') == 'true';
		
		$cps = catalog_CompiledproductService::getInstance();
		$cps->setPositionsByShelfAndShopAndLang($shelf, $shop, $lang, $productOrder, $applyToAllLangs);
		
		$result['nodes'] = catalog_CompiledproductService::getInstance()->getOrderInfosByShelfAndShopAndLang($shelf, $shop, $lang);
		
		return $this->sendJSON($result);
	}
}