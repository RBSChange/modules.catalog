<?php
/**
 * catalog_patch_0377
 * @package modules.catalog
 */
class catalog_patch_0377 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Preferences cleaning.
		/* @var $prefs catalog_persistentdocument_preferences */
		$prefs = ModuleService::getInstance()->getPreferencesDocument('catalog');
		$prefs->setSynchronizeComplementary(false);
		$prefs->setSynchronizeSimilar(false);
		$prefs->setSuggestComplementaryFeederClass('none');
		$prefs->setSuggestSimilarFeederClass('none');
		$prefs->setSuggestUpsellFeederClass('none');
		$prefs->setAutoFeedComplementaryMaxCount(0);
		$prefs->setAutoFeedSimilarMaxCount(0);
		$prefs->setAutoFeedUpsellMaxCount(0);
		$prefs->save();

		// Delete the old catalog_AutoFeedRelatedProductsTask planned task.
		$query = task_PlannedtaskService::getInstance()->createQuery();
		$query->add(Restrictions::eq('systemtaskclassname', 'catalog_AutoFeedRelatedProductsTask'));
		$query->delete();
		
		// Migrate complementary, similar and upsell properties in declined products.
		$batchPath = 'modules/catalog/patch/0377/batchMigrateDeclined.php';
		$chunkSize = 25;
		$id = 0;
		$errors = array();
		while ($id >= 0)
		{
			$result = f_util_System::execScript($batchPath, array($id, $chunkSize));
			$lines = array_reverse(explode(PHP_EOL, $result));
			foreach ($lines as $line)
			{
				if (preg_match('/^id:([0-9-]+)$/', $line, $matches))
				{
					$id = $matches[1];
					break;
				}
				else
				{
					$errors[] = $line;
				}
			}
		}		
		if (count($errors))
		{
			throw new Exception(implode(PHP_EOL, array_reverse($errors)));
		}
		
		// Migrate complementary, similar and upsell properties in products.
		$batchPath = 'modules/catalog/patch/0377/batchMigrateProducts.php';
		$chunkSize = 25;
		$id = 0;
		$errors = array();
		while ($id >= 0)
		{
			$result = f_util_System::execScript($batchPath, array($id, $chunkSize));
			$lines = array_reverse(explode(PHP_EOL, $result));
			foreach ($lines as $line)
			{
				if (preg_match('/^id:([0-9-]+)$/', $line, $matches))
				{
					$id = $matches[1];
					break;
				}
				else
				{
					$errors[] = $line;
				}
			}
		}		
		if (count($errors))
		{
			throw new Exception(implode(PHP_EOL, array_reverse($errors)));
		}
	}
}