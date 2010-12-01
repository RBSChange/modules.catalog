<?php
/**
 * catalog_SetDefaultShopAction
 * @package modules.catalog.actions
 */
class catalog_SetDefaultShopAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$shop = $this->getShop($request);
		$shop->getDocumentService()->setAsDefault($shop);

		$result['id'] = $shop->getId();
		$result['isDefault'] = $shop->getIsDefault();
		
		return $this->sendJSON($result);
	}
	
	/**
	 * @param Request $request
	 * @return catalog_persistentdocument_shop
	 */	
	private function getShop($request)
	{
		$shop = $this->getDocumentInstanceFromRequest($request);
		if ($shop instanceof catalog_persistentdocument_shop) 
		{
			return $shop;
		}
		throw new BaseException('Invalid shop', 'modules.catalog.errors.compileproductAction.Invalid-shop');
	}
}