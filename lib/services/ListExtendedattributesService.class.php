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
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return list_Item[]
	 */
	public function getItems()
	{
		$attrService = catalog_AttributefolderService::getInstance();
		$attributes = $attrService->getAttributeFolder()->getAttributes();
		$items = array();
		foreach ($attributes as $attribute)
		{
			/* @var $attribute catalog_AttributeDefinition */
			$items[] = new list_Item(
				$attribute->getLabel(), $attribute->getCode()
			);
		}
		return $items;
	}

	/**
	 * @return String
	 */
	public function getDefaultId()
	{
		return 'none';
	}
}