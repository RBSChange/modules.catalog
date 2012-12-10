<?php
/**
 * catalog_patch_0382
 * @package modules.catalog
 */
class catalog_patch_0382 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$batchPath = 'modules/catalog/patch/0382/batchPublishKititems.php';
		$chunkSize = 50;
		$id = 0;
		$errors = array();
		while ($id >= 0)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info('[PATCH catalog/0382] $id = ' . $id . ', $chunkSize = ' . $chunkSize);
			}
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