<?php
/**
 * catalog_GetModelsInfosForProductSelectorAction
 * @package modules.catalog.actions
 */
class catalog_GetModelsInfosForProductSelectorAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$result['declinedModels'] = DocumentHelper::expandModelList('[modules_catalog/declinedproduct]');
		$result['declinationModels'] = DocumentHelper::expandModelList('[modules_catalog/productdeclinations]');

		return $this->sendJSON($result);
	}
}