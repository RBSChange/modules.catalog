<?php
/**
 * commands_catalog_CompileCrossitems
 * @package modules.catalog.command
 */
class commands_catalog_CompileCrossitems extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	public function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	public function getDescription()
	{
		return "Compile crossitems";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->message("== Compile crossitems ==");

		$batchPath = 'modules/catalog/lib/bin/batchCrossitemsCompile.php';
		$errors = array();
		$chunkSize = Framework::getConfigurationValue('modules/catalog/crossitemsCompilationChunkSize', 100);
		$id = 0;
		$hasErrors = false;
		while ($id >= 0)
		{
			$result = f_util_System::execScript($batchPath, array($id, $chunkSize, 'false'));			
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
					$hasErrors = true;
					$this->errorMessage($line);
				}
			}
		}

		if (!$hasErrors)
		{
			$this->quitOk("Command successfully executed");
		}
		else
		{
			$this->quitError("Some errors occured");
		}
	}
}