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
		$price = catalog_persistentdocument_price::getInstanceById($this->getDocumentIdFromRequest($request));
		$parts = explode(',', $request->getParameter('value'));

		$shop = $price->getShop();
		$valueToSet = doubleval($parts[0]);
		$taxZone = $shop->getBoTaxZone();		
		if ($taxZone !== null && $valueToSet > 0)
		{
			$rate = catalog_TaxService::getInstance()->getTaxRate($shop->getId(), $price->getTaxCategory(), $taxZone);
			$valueToSet = doubleval($parts[0]) / (1 + $rate);
		}
		$detail = $request->getParameter('detail');
		$start = date_Converter::convertDateToGMT($request->getParameter('start'));
		$end = date_Converter::convertDateToGMT($request->getParameter('end'));
		$price->getDocumentService()->createDiscountOnPrice($price, $valueToSet, $detail, $start, $end);
		return $this->sendJSON($result);
	}
}