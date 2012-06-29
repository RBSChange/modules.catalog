<?php
/**
 * @package modules.catalog
 * @method catalog_ListProductsuggestionfeedersService getInstance()
 */
class catalog_ListProductsuggestionfeedersService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array(new list_Item(LocaleService::getInstance()->trans('m.catalog.bo.lists.product-suggestion-feeder.none', array('ucf')), 'none'));
		
		$ms = ModuleService::getInstance();
		foreach ($ms->getPackageNames() as $module)
		{
			$feeders = Framework::getConfiguration('modules/'.$ms->getShortModuleName($module).'/modulesCatalogProductSuggestionFeeder', false);
			if (is_array($feeders))
			{
				foreach ($feeders as $feeder)
				{
					$items[] = new list_Item(f_util_ClassUtils::callMethod($feeder, 'getInstance')->getLabel(), $feeder);
				}
			}
		}
		
		return $items;
	}

	/**
	 * @return string
	 */
	public final function getDefaultId()
	{
		return 'none';
	}
}