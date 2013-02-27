<?php
class catalog_ReIndexProductsByShelfTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$chunkSize = Framework::getConfigurationValue('modules/catalog/compilationChunkSize');
		$shelf = DocumentHelper::getDocumentInstance($this->getParameter('shelfId'));
		if (!($shelf instanceof catalog_persistentdocument_shelf))
		{
			Framework::error(__METHOD__ . ' Invalid shelfId: ' . $this->getParameter('shelfId'));
		}
		
		$batchPath = f_util_FileUtils::buildRelativePath('modules', 'catalog', 'lib', 'bin', 'batchReIndexProductsByShelf.php');
		$id = 0;
		$errors = array();
		while ($id >= 0)
		{
			$this->plannedTask->ping();
			$result = f_util_System::execScript($batchPath, array($shelf->getId(), $id, $chunkSize));
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