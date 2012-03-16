<?php
/**
 * catalog_patch_0370
 * @package modules.catalog
 */
class catalog_patch_0370 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/product.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'product');
		$newProp = $newModel->getPropertyByName('shippedIn');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'product', $newProp);
		
		$this->executeLocalXmlScript('list.xml');
	}
}