<?php
class catalog_BackgroundCompileTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$ids = catalog_ProductService::getInstance()->getProductIdsToCompile();
		$maxIteration = 4;
		while ($maxIteration > 0 && count($ids))
		{
			$this->compileProductIds($ids);
			$ids = catalog_ProductService::getInstance()->getProductIdsToCompile();
			$maxIteration--;
		}
	}
	
	protected function compileProductIds($ids)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' product to compile: ' . count($ids));		
		}
		f_persistentdocument_PersistentProvider::getInstance()->refresh();		
		$batchPath = 'modules/catalog/lib/bin/batchCompile.php';
		foreach (array_chunk($ids, 100) as $chunk)
		{
			$result = f_util_System::execHTTPScript($batchPath, $chunk);
			// Log fatal errors...
			if ($result != 'OK')
			{
				Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result: "' . $result . '"');
			}
		}		
	}
}