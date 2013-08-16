<?php
/**
 * catalog_patch_0385
 * @package modules.catalog
 */
class catalog_patch_0385 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
        // Update product model
        $newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/product.xml');
        $newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'product');
        $newProp = $newModel->getPropertyByName('shippingWeight');
        f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'product', $newProp);


        // Update declinedproduct model
        $newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/declinedproduct.xml');
        $newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'declinedproduct');
        $newProp = $newModel->getPropertyByName('shippingWeight');
        f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'declinedproduct', $newProp);
        $newProp = $newModel->getPropertyByName('synchronizeWeight');
        f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'declinedproduct', $newProp);

		$this->execChangeCommand('compile-db-schema');
	}
}