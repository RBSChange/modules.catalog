<?php
/**
 * catalog_LoadPriceInfoToAddDiscountAction
 * @package modules.catalog.actions
 */
class catalog_LoadPriceInfoToAddDiscountAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$price = $this->getPrice($request);
		$dateFormat = f_Locale::translateUI('&modules.uixul.bo.datepicker.calendar.dataWriterTimeFormat;');
		$startDate = $price->getUIStartpublicationdate();
		$endDate = $price->getUIEndpublicationdate();
		$result['currentPriceValue'] = $price->getBoValue();
		$result['currentStartDate'] = $startDate;
		$result['currentStartDateFormatted'] = $startDate ? date_DateFormat::format($startDate, $dateFormat) : '-';
		$result['currentEndDate'] = $endDate;
		$result['currentEndDateFormatted'] = $endDate ? date_DateFormat::format($endDate, $dateFormat) : '-';
		$result['discountPriceValue'] = $price->getBoDiscountValue();
		
		return $this->sendJSON($result);
	}
	
	/**
	 * @return catalog_persistentdocument_price
	 */
	private function getPrice($request)
	{
		$price = $this->getDocumentInstanceFromRequest($request);
		if (!$price instanceof catalog_persistentdocument_price)
		{
			throw new BaseException('Invalid price!', 'modules.catalog.bo.doceditor.dialog.add-discount-on-price.Invalid-price');
		}
		return $price;
	}
}