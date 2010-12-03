<?php
/**
 * catalog_patch_0352
 * @package modules.catalog
 */
class catalog_patch_0352 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
		$newProp = $newModel->getPropertyByName('isDefault');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		
		// Until now, there were no more than one published shop by website.
		foreach (catalog_ShopService::getInstance()->createQuery()->add(Restrictions::published())->find() as $shop)
		{
			$shop->setIsDefault(true);
			$shop->save();
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
		return '0352';
	}
}