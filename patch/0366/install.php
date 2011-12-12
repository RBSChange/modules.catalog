<?php
/**
 * catalog_patch_0366
 * @package modules.catalog
 */
class catalog_patch_0366 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('update.xml');
	}
}