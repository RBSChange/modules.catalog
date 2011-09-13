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
		$batchPath = 'modules/catalog/lib/bin/batchCompile.php';
		$errors = array();
		
		while ($maxIteration > 0 && count($ids))
		{
			foreach (array_chunk($ids, Framework::getConfigurationValue('modules/catalog/compilationChunkSize', 100)) as $chunk)
			{
				$this->plannedTask->ping();
				$result = f_util_System::execHTTPScript($batchPath, $chunk);
				if ($result != 'OK')
				{
					$errors[] = $result;
				}
			}
			$ids = catalog_ProductService::getInstance()->getProductIdsToCompile();
			$maxIteration--;
		}
		
		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}
		
	}
}