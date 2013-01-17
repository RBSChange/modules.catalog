<?php
/**
 * catalog_patch_0383
 * @package modules.catalog
 */
class catalog_patch_0383 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('list.xml');
	}
}