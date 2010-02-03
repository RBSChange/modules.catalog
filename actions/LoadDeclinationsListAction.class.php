<?php
class catalog_LoadDeclinationsListAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$product = DocumentHelper::getDocumentInstance($request->getParameter('productId'));
		$declinations = $product->getDeclinationArray();
		
		$data = array();
		$data['totalCount'] = count($declinations);
		$data['count'] = count($declinations);
		$data['offset'] = 0;
		$data['nodes'] = array();
		
		$lang = RequestContext::getInstance()->getLang();
		foreach ($declinations as $declination)
		{
			$langAvailable = $declination->getI18nInfo()->isLangAvailable($lang);
			
			$data['nodes'][] = array(
				'id' => $declination->getId(),
				'langAvailable' => $langAvailable,
				'label' => ($langAvailable ? $declination->getLabel() : ($declination->getVoLabel() . ' [' . f_Locale::translateUI('&modules.uixul.bo.languages.' . ucfirst($declination->getLang()) . ';') . ']')),
				'codeReference' => $declination->getCodeReference(),
				'stockQuantity' => $declination->getStockQuantity(),
				'stockLevel' => $declination->getStockLevel()
			);
		}
		return $this->sendJSON($data);
	}
}