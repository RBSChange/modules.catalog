<?php
/**
 * catalog_patch_0362
 * @package modules.catalog
 */
class catalog_patch_0362 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('indexer', array('import-model', 'modules_catalog/compiledproduct'));
	}
}