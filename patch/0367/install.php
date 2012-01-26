<?php
/**
 * catalog_patch_0367
 * @package modules.catalog
 */
class catalog_patch_0367 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		foreach (catalog_ShelfService::getInstance()->createQuery()->find() as $document)
		{
			/* @var $document catalog_persistentdocument_shelf */
			$visual = $document->getVisual();
			if ($visual !== null)
			{
				foreach ($document->getTopicArray() as $topic)
				{
					$topic->setVisual($document->getVisual());
					$topic->save();
				}
			}
		}
	}
}