<?php
/**
 * catalog_patch_0316
 * @package modules.catalog
 */
class catalog_patch_0316 extends patch_BasePatch
{
	//  by default, isCodePatch() returns false.
	//  decomment the following if your patch modify code instead of the database structure or content.
	/**
	 * Returns true if the patch modify code that is versionned.
	 * If your patch modify code that is versionned AND database structure or content,
	 * you must split it into two different patches.
	 * @return Boolean true if the patch modify code that is versionned.
	 */
	//	public function isCodePatch()
	//	{
	//		return true;
	//	}
	

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		try
		{
			$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
			$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
			$newProp = $newModel->getPropertyByName('nbproductperpage');
			f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
			
			$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/shop.xml');
			$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'shop');
			$newProp = $newModel->getPropertyByName('enableComments');
			f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'shop', $newProp);
		
		}
		catch ( Exception $e )
		{
			$this->logError($e->getMessage());
		}
		foreach ( catalog_ShopService::getInstance()->createQuery()->find() as $shop )
		{
			$defaultProdPerPagePrefs = ModuleService::getInstance()->getPreferenceValue('catalog', 'defaultProductsPerPage');
			if ($defaultProdPerPagePrefs < 1 || $defaultProdPerPagePrefs > 250)
			{
				$defaultProdPerPagePrefs = 10;
			}
			$shop->setNbproductperpage($defaultProdPerPagePrefs);
			$shop->setEnableComments(ModuleService::getInstance()->getPreferenceValue('catalog', 'enableComments'));
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
		return '0316';
	}
}