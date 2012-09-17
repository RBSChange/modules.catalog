<?php
class catalog_BackgroundCrossitemsCompileTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$batchPath = 'modules/catalog/lib/bin/batchCrossitemsCompile.php';
		$errors = array();
		
		$chunkSize = Framework::getConfigurationValue('modules/catalog/crossitemsCompilationChunkSize', 100);
		$id = 0;
		$errors = array();
		while ($id >= 0)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execScript($batchPath, array($id, $chunkSize, 'true'));
			if (!$result)
			{
				$errors[] = 'No result';
				break;
			}
			
			$lines = array_reverse(explode(PHP_EOL, $result));
			$newId = $id;
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
			
			if ($newId != $id)
			{
				$id = $newId;
			}
			else
			{
				break;
			}
		}
		
		if (count($errors))
		{
			throw new Exception(implode(PHP_EOL, array_reverse($errors)));
		}
	}
}