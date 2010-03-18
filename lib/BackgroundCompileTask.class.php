<?php
class catalog_BackgroundCompileTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 *
	 */
	protected function execute()
	{
		chdir(WEBEDIT_HOME);
		
		$batchPath = FileResolver::getInstance()
			->setPackageName('modules_catalog')
			->setDirectory('lib'. DIRECTORY_SEPARATOR . 'bin')
			->getPath('batchCompile.php');
		$ids = catalog_ProductService::getInstance()->getProductIdsToCompile();
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' product to compile: ' . count($ids));		
		}
		foreach (array_chunk($ids, 10) as $batch)
		{
			$processHandle = popen('php ' . $batchPath . ' '. implode(' ', $batch), 'r');
			while ($string = fread($processHandle, 1024))
			{
				echo $string;
			}
			pclose($processHandle);
		}	
	}
}