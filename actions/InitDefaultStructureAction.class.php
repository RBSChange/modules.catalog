<?php
/**
 * catalog_InitDefaultStructureAction
 * @package modules.catalog.actions
 */
class catalog_InitDefaultStructureAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$shop = $this->getDocumentInstanceFromRequest($request);
		if ($shop instanceof catalog_persistentdocument_shop)
		{
			$systemTopic = $shop->getTopic();
			$node = TreeService::getInstance()->getInstanceByDocument($systemTopic);
			if (count($node->getChildren('modules_website/page')) == 0)
			{
				if (Framework::hasConfiguration('modules/catalog/structure/shopDefaultStructure'))
				{
					$scriptPath = f_util_FileUtils::buildWebeditPath(Framework::getConfiguration('modules/catalog/structure/shopDefaultStructure'));
				}
				else
				{
					$scriptPath = f_util_FileUtils::buildWebeditPath('modules', 'catalog', 'setup', 'shopDefaultStructure.xml');
				}
				catalog_ShopService::getInstance()->initDefaultStructure($shop, $scriptPath);
			}
			else
			{
				throw new BaseException('Shop already contains pages', 'modules.catalog.bo.actions.Shop-already-contains-pages');
			}
		}
		else
		{
			throw new BaseException('Invalid shop', 'modules.catalog.bo.actions.Invalid-shop');
		}
		return $this->sendJSON(array('id' => $shop->getId()));
	}
}