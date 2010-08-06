<?php
/**
 * catalog_patch_0312
 * @package modules.catalog
 */
class catalog_patch_0312 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Add listName property.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/productlist.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'productlist');
		$newProp = $newModel->getPropertyByName('listName');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'productlist', $newProp);
		
		// Fill listName property with 'favorite' (default value).
		foreach (catalog_ProductlistService::getInstance()->createQuery()->find() as $list)
		{
			$list->setListName(catalog_Productlist::FAVORITE);
			$list->save();
		}
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'catalog';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0312';
	}
}