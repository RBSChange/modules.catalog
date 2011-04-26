<?php
/**
 * catalog_patch_0358
 * @package modules.catalog
 */
class catalog_patch_0358 extends patch_BasePatch
{ 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('compile-config');
		$this->execChangeCommand('compile-locales', array('catalog'));
		
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
		$newProp = $newModel->getPropertyByName('orderProcessClassName');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		$this->executeModuleScript('order-process.xml', 'catalog');
	}
}