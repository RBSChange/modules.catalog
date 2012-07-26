<?php
/**
 * CompileCatalogTask
 * @package modules.catalog
 */
class commands_CompileCatalog extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Compile the products.";
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile catalog ==");
		$this->loadFramework();
		
		$batchPath = 'modules/catalog/lib/bin/batchCompile.php';
		$chunkSize = Framework::getConfigurationValue('modules/catalog/compilationChunkSize', 100);
		$compileAll = 1;
		$lastId = 0;	
		while ($lastId >= 0)
		{
			$result = f_util_System::execScript($batchPath, array($lastId, $chunkSize, $compileAll));
			if (!$result)
			{
				
				Framework::error(__METHOD__ , ' No result');
				break;
			}
			$errors = array();
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
		
			if (count($errors))
			{
				Framework::error(__METHOD__ , ' ', implode(PHP_EOL, array_reverse($errors)));
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
					$this->log($toCompile . ' products as marqued to compile. Execute new iteration.');
					$lastId = 0;
					$compileAll = 0;
				}
				else
				{
					break;
				}
			}
		}
		$this->quitOk("All products are compiled successfully.");
	}
}