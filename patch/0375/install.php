<?php
/**
 * catalog_patch_0375
 * @package modules.catalog
 */
class catalog_patch_0375 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('init.xml');
		$this->execChangeCommand('generate-database', array ('catalog'));
	}
}