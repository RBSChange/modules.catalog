<?php
/**
 * catalog_patch_0320
 * @package modules.catalog
 */
class catalog_patch_0320 extends patch_BasePatch
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
		$kititemIds = catalog_KititemService::getInstance()->createQuery()
			->setProjection(Projections::property("id"))
			->add(Restrictions::isNull("product"))->findColumn("id");
		if (f_util_ArrayUtils::isEmpty($kititemIds))
		{
			$this->log("No kititem without product");
			return;
		}
		$this->logWarning(count($kititemIds) . " kititems to unpublish");
		
		$batch = f_util_FileUtils::buildRelativePath("modules/catalog/patch/0320/alteritems.php");
		foreach (array_chunk($kititemIds, 50) as $chunk)
		{
			$res = f_util_System::execHTTPScript($batch, array($chunk));
			if ($res != "OK")
			{
				$this->logError("Unable to get alteritems result");
			}
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
		return '0320';
	}
}