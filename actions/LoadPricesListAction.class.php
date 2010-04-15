<?php
class catalog_LoadPricesListAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$prices = catalog_PriceService::getInstance()->getPricesForDate(
			$request->getParameter('date'), 
			$request->getParameter('productId'),
			$request->getParameter('shopId'),
			$request->getParameter('sortOnField'),
			$request->getParameter('sortDirection'), 
			$request->getParameter('targetId') 
		);
		$data = array();
		$data['totalCount'] = count($prices);
		$data['count'] = count($prices);
		$data['offset'] = 0;
		$data['nodes'] = array();
		foreach ($prices as $price)
		{
			$data['nodes'][] = array(
				'id' => $price->getId(),
				'valueWithTax' => $price->getFormattedValueWithTax(),
				'isDiscount' => f_Locale::translateUI('&modules.uixul.bo.general.' . ($price->isDiscount() ? 'Yes' : 'No') . ';'),
				'targetLabel' => $price->getTargetLabel(),
				'thresholdMin' => $price->getThresholdMin(),
				'startpublicationdate' => $price->getUIStartpublicationdate(),
				'endpublicationdate' => $price->getUIEndpublicationdate(),
			);
		}
		return $this->sendJSON($data);
	}
}