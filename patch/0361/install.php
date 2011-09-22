<?php
/**
 * catalog_patch_0361
 * @package modules.catalog
 */
class catalog_patch_0361 extends patch_BasePatch
{ 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$declinationIds = catalog_ProductdeclinationService::getInstance()->createQuery()
			->setProjection(Projections::property("id"))
			->findColumn("id");
		foreach (array_chunk($declinationIds, 10) as $chunkIds)
		{
			$res = f_util_System::execHTTPScript("modules/catalog/patch/0361/synchronizeSimilars.php", $chunkIds);
			if ($res != "OK")
			{
				$this->logError("Error: ".$res);
			}
		}
	}
}