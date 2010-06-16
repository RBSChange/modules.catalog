<?php
/**
 * catalog_BlockTopShelvesMenuAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockTopShelvesMenuAction extends website_BlockAction
{	
	/**
	 * @see website_BlockAction::execute()
	 *
	 * @param f_mvc_Request $request
	 * @param f_mvc_Response $response
	 * @return String
	 */
	function execute($request, $response)
	{
		$maxLevel = $this->getConfiguration()->getMaxlevel();
		$shop = catalog_ShopService::getInstance ()->getCurrentShop();
		if ($shop == null)
		{
			return website_BlockView::NONE;
		}
		$menu = website_WebsiteModuleService::getInstance ()->getMenuByTopic($shop->getTopic(), $maxLevel);
		$request->setAttribute('shop', $shop);
		$request->setAttribute('menu', $menu);
		$request->setAttribute('maxlevel', $maxLevel);
		return website_BlockView::SUCCESS;
	}
}