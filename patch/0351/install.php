<?php
/**
 * catalog_patch_0351
 * @package modules.catalog
 */
class catalog_patch_0351 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$archivePath = f_util_FileUtils::buildWebeditPath('modules/catalog/patch/0351/compiledproduct-1.xml');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/compiledproduct.xml');
		$oldModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($archivePath), 'catalog', 'compiledproduct');
		$oldProp = $oldModel->getPropertyByName('indexed');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'compiledproduct');
		$newProp = $newModel->getPropertyByName('primary');
		f_persistentdocument_PersistentProvider::getInstance()->renameProperty('catalog', 'compiledproduct', $oldProp, $newProp);
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
		return '0351';
	}
}