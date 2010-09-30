<?php
/**
 * catalog_AddDiscountOnPriceAction
 * @package modules.catalog.actions
 */
class catalog_AddDiscountOnPriceAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$price = DocumentHelper::getDocumentInstance($request->getParameter('priceId'), 'modules_catalog/price');
		$value = $request->getParameter('value');
		$detail = $request->getParameter('detail');
		$start = date_Converter::convertDateToGMT($request->getParameter('start'));
		$end = date_Converter::convertDateToGMT($request->getParameter('end'));
		$price->getDocumentService()->createDiscountOnPrice($price, $value, $detail, $start, $end);
		
		return $this->sendJSON($result);
	}
}