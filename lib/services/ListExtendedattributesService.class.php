<?php
/**
 * @package modules.catalog
 * @method catalog_ListExtendedattributesService getInstance()
 */
class catalog_ListExtendedattributesService extends change_BaseService implements list_ListItemsService
{
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
			/* @var $attribute catalog_AttributeDefinition */
			$items[] = new list_Item(
				$attribute->getLabel(), $attribute->getCode()
			);
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