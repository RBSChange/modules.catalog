<?php
class catalog_BackgroundCompileTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$ids = catalog_ProductService::getInstance()->getProductIdsToCompile();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' product to compile: ' . count($ids));		
		}
		$batchPath = 'modules/catalog/lib/bin/batchCompile.php';
		foreach (array_chunk($ids, 10) as $chunk)
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