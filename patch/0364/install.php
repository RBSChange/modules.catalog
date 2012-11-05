<?php
/**
 * catalog_patch_0364
 * @package modules.catalog
 */
class catalog_patch_0364 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		if (list_ListService::getInstance()->getByListId('modules_catalog/shippingmodeoptions') == null)
		{
			$this->executeLocalXmlScript('list.xml');
		}
	}
}