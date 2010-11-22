<?php
/**
 * catalog_patch_0350
 * @package modules.catalog
 */
class catalog_patch_0350 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log("Compile document...");
		$this->execChangeCommand("compile-documents");
		
		$this->log("Add codeReference property on shelf document...");
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shelf.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shelf');
		$newProp = $newModel->getPropertyByName('codeReference');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shelf', $newProp);
		
		$this->log("Add codeReference property on shop document...");
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
		$newProp = $newModel->getPropertyByName('codeReference');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
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
		return '0350';
	}
}