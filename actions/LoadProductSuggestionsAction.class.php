<?php
/**
 * catalog_LoadProductSuggestionsAction
 * @package modules.catalog.actions
 */
class catalog_LoadProductSuggestionsAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$productId = $request->getParameter('productId');
		$excludedIds = explode(',', $request->getParameter('alreadySelectedIds'));
		$excludedIds[] = $productId;
		$parameters = array(
			'productId' => $productId,
			'maxResults' => $request->getParameter('maxResults'),
			'excludedId' => $excludedIds
		);
		$productsInfos = f_util_ClassUtils::callMethod($request->getParameter('feederClass'), 'getInstance')->getProductArray($parameters);
		$data = array('nodes' => array());
		foreach ($productsInfos as $productInfos)
		{
			$product = $productInfos[0];
			$data['nodes'][] = array(
				'id' => $product->getId(),
				'label' => $product->getLabel(),
				'codeReference' => $product->getCodeReference(),
				'weight' => $productInfos[1],
				'icon' => $product->getPersistentModel()->getIcon()
			);		
		}
		return $this->sendJSON($data);
	}
}