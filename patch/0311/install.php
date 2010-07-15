<?php
/**
 * catalog_patch_0311
 * @package modules.catalog
 */
class catalog_patch_0311 extends patch_BasePatch
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
		// Implement your patch here.
		$this->execChangeCommand('compile-documents', array());
		$newPath = f_util_FileUtils::buildWebeditPath('modules/catalog/persistentdocument/compiledproduct.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'catalog', 'compiledproduct');
		$newProp = $newModel->getPropertyByName('topshelfId');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('catalog', 'compiledproduct', $newProp);	
		echo $this->execChangeCommand('catalog.compile-catalog', array());
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
		return '0311';
	}
}