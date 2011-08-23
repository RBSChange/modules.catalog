<?php
/**
 * @package module.catalog
 */
class catalog_ListExtendedattributesService extends BaseService
{
	/**
	 * @var catalog_ListExtendedattributesService
	 */
	private static $instance;

	/**
	 * @return catalog_ListShippingFiltersService
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
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$attrService = catalog_AttributefolderService::getInstance();
		$attributes = $attrService->getAttributeFolder()->getAttributes();
		$items = array();
		foreach ($attributes as $attribute)
		{
			$items[] = new list_Item(
				$attribute["label"],
				$attribute["code"]
			);
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