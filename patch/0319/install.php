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
		// FIX #32733
		$rootId = ModuleService::getInstance()->getRootFolderId("catalog");
		$noshelfFolderService = catalog_NoshelfproductfolderService::getInstance();
		$folder = $noshelfFolderService->createQuery()->findUnique();
		if ($folder === null)
		{
			$folder = $noshelfFolderService->getNewDocumentInstance();
			$folder->save($rootId);	
		}
		$ts = TreeService::getInstance();
		$shopFolderId = catalog_ShopfolderService::getInstance()->createQuery()->findUnique()->getId();
		$folderNode = $ts->getInstanceByDocument($folder); 
		$ts->moveToNextSiblingForNode($folderNode, $shopFolderId);
		
		// FIX #35085
		$newDeclinedProduct = f_util_FileUtils::buildWebeditPath("modules/catalog/patch/0319/declinedproduct.png");
		$oldDeclinedProduct = f_util_FileUtils::buildWebeditPath("libs/icons/small/declinedproduct.png");
		if (is_writeable($oldDeclinedProduct))
		{
			f_util_FileUtils::cp($newDeclinedProduct, $oldDeclinedProduct, f_util_FileUtils::OVERRIDE);
		}
		else
		{
			$this->logWarning("Could not create libs/icons/small/declinedproduct.png please do it manually using ".$newDeclinedProduct);
		}
		
		$this->log("clear-webapp-cache");
		$this->execChangeCommand("clear-webapp-cache");
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