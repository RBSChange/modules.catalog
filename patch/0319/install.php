<?php
/**
 * catalog_patch_0319
 * @package modules.catalog
 */
class catalog_patch_0319 extends patch_BasePatch
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
		$rootId = ModuleService::getInstance()->getRootFolderId("catalog");
		$folder = catalog_NoshelfproductfolderService::getInstance()->getNewDocumentInstance();
		$folder->save($rootId);
		$ts = TreeService::getInstance();
		$shopFolderId = catalog_ShopfolderService::getInstance()->createQuery()->findUnique()->getId();
		$folderNode = $ts->getInstanceByDocument($folder); 
		$ts->moveToNextSiblingForNode($folderNode, $shopFolderId);
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
		return '0319';
	}
}