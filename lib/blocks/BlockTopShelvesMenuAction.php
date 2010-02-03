<?php
/**
 * catalog_BlockTopShelvesMenuAction
 * @package modules.catalog.lib.blocks
 */
class catalog_BlockTopShelvesMenuAction extends block_BlockAction
{	
	/**
	 * @param block_BlockContext $context
	 * @param block_BlockRequest $request
	 * @return String view name
	 */
	public function execute($context, $request)
	{
		if ($context->inIndexingMode())
		{
			return block_BlockView::NONE;
		}
		
		// Get the max level.
		// Initialize it to 1 if unset.
		if (!$this->hasParameter('maxlevel'))
		{
			$this->setParameter('maxlevel', 1);
		}
		// Let's decrement it because the getMenuByTopic needs it...
		$maxLevel = $this->getParameter('maxlevel') - 1;
		
		$shop = catalog_ShopService::getInstance()->getCurrentShop();
			
		$menu = website_WebsiteModuleService::getInstance()->getMenuByTopic($shop->getTopic(), $maxLevel);
		$this->setParameter('shop', $shop);
		$this->setParameter('menu', $menu);
		
		return block_BlockView::SUCCESS;
	}
}