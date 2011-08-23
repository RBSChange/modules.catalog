<?php
/**
 * @package module.catalog
 */
class catalog_ListProductsuggestionfeedersService extends BaseService
{
	/**
	 * @var catalog_ListProductsuggestionfeedersService
	 */
	private static $instance;

	/**
	 * @return catalog_ListProductsuggestionfeedersService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return array<list_Item>
	 */
	public final function getItems()
	{
		$items = array(new list_Item(LocaleService::getInstance()->transBO('m.catalog.bo.lists.product-suggestion-feeder.none', array('ucf')), 'none'));
		
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
	 * @return String
	 */
	public final function getDefaultId()
	{
		return 'none';
	}
}