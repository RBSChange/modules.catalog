<?php
/**
 * catalog_patch_0373
 * @package modules.catalog
 */
class catalog_patch_0373 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		foreach (catalog_AttributefolderService::getInstance()->createQuery()->find() as $folder)
		{
			/* @var $folder generic_persistentdocument_folder */
			$folder->setLabel('m.catalog.document.attributefolder.document-name');
			$folder->save();
		}
	}
}