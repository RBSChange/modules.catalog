<?php
/**
 * catalog_patch_0306
 * @package modules.catalog
 */
class catalog_patch_0306 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('list.xml');
		
		// Add synchronizePrices property.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/declinedproduct.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'declinedproduct');
		$newProp = $newModel->getPropertyByName('synchronizePrices');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'declinedproduct', $newProp);
		
		// Set synchronizePrices to false on existing products.
		foreach (catalog_DeclinedproductService::getInstance()->createQuery()->find() as $product)
		{
			$product->setSynchronizePrices(false);
			$product->save();
		}
		
		// Add replicatedFrom property.
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/price.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'price');
		$newProp = $newModel->getPropertyByName('replicatedFrom');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'price', $newProp);
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
		return '0306';
	}
}