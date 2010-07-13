<?php
/**
 * catalog_LoadProductPublicationInShopInfosAction
 * @package modules.catalog.actions
 */
class catalog_LoadProductPublicationInShopInfosAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$product = $this->getDocumentInstanceFromRequest($request);
		$result['infos'] = $product->getDocumentService()->getPublicationInShopsInfos($product);

		return $this->sendJSON($result);
	}
}