<?php
/**
 * catalog_LoadProductPublicationInShopInfosAction
 * @package modules.catalog.actions
 */
class catalog_LoadProductPublicationInShopInfosAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$product = $this->getDocumentInstanceFromRequest($request);
		$result['infos'] = $product->getDocumentService()->getPublicationInShopsInfos($product);

		return $this->sendJSON($result);
	}
}