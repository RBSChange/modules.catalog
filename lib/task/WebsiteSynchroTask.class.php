<?php
class catalog_WebsiteSynchroTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		if (!catalog_ModuleService::getInstance()->useAsyncWebsiteUpdate())
		{
			return;
		}

		$ids = catalog_ModuleService::getInstance()->resetAsyncWebsiteUpdateIds();
		$batchPath = 'modules/catalog/lib/bin/websiteSynchro.php';
		while (is_array($ids) && count($ids))
		{
			$idsToChunks = $ids;
			$ids = array();
			foreach (array_chunk($idsToChunks, 2) as $chunk)
			{
				$this->plannedTask->ping();
				
				$result = f_util_System::execScript($batchPath, $chunk);
				if ($result)
				{
					$lines = array_reverse(explode(PHP_EOL, $result));
					foreach ($lines as $line)
					{
						if (preg_match('/^id:([0-9-]+)$/', $line, $matches))
						{
							$ids[] = intval($matches[1]);
						}
					}
				}
			}		
		}
	}
}