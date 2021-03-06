<?php
/**
 * catalog_RemoveDiscountAction
 * @package modules.catalog.actions
 */
class catalog_RemoveDiscountAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$price = DocumentHelper::getDocumentInstance($request->getParameter('cmpref'), 'modules_catalog/price');
		$price->getDocumentService()->removeDiscountFromPrice($price);

		return $this->sendJSON($result);
	}
}