<?php
/**
 * catalog_patch_0314
 * @package modules.catalog
 */
class catalog_patch_0314 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
		$newProp = $newModel->getPropertyByName('enableCaptchaForAlerts');
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
		return '0314';
	}
}