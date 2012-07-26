<?php
class catalog_BackgroundCompileTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$toCompile = catalog_ProductService::getInstance()->getCountProductIdsToCompile();
		if ($toCompile == 0)
		{
			Framework::info(__METHOD__ . ' No product has marqued to recompile.');
			return;
		}
		Framework::info(__METHOD__ . ' ' . $toCompile .' products has marqued to recompile');
		$batchPath = 'modules/catalog/lib/bin/batchCompile.php';
		$chunkSize = Framework::getConfigurationValue('modules/catalog/compilationChunkSize', 100);
		$maxIteration = 4;
		$lastId = 0;
		$errors = array();
		while ($maxIteration > 0 && $lastId >= 0)
		{
			
			$this->plannedTask->ping();
			$result = f_util_System::execScript($batchPath, array($lastId, $chunkSize, 0));
			if (!$result)
			{
				$errors[] = 'No result';
				break;
			}

			$lines = array_reverse(explode(PHP_EOL, $result));
			$newId = $lastId;
			foreach ($lines as $line)
			{
				if (preg_match('/^id:([0-9-]+)$/', $line, $matches))
				{
					$newId = $matches[1];
					break;
				}
				else
				{
					$errors[] = $line;
				}
			}
		
			if ($newId != $lastId)
			{
				$lastId = $newId;
			}
			else
			{
				$toCompile = catalog_ProductService::getInstance()->getCountProductIdsToCompile();
				if ($toCompile > 0)
				{
					$lastId = 0;
					$maxIteration--;
					Framework::info(__METHOD__ . ' ' . $toCompile .' products has marqued to recompile for iteration ' . (4 - $maxIteration));
				}
				else
				{
					$maxIteration = 0;
				}
			}
		}
		
		if (count($errors))
		{
			throw new Exception(implode(PHP_EOL, array_reverse($errors)));
		}
	}
}