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

		$batchPath = FileResolver::getInstance()
			->setPackageName('modules_catalog')
			->setDirectory('lib'. DIRECTORY_SEPARATOR . 'bin')
			->getPath('batchCompile.php');
			
		$ids = catalog_ProductService::getInstance()->getAllProductIdsToCompile();
		$count = count($ids);			
		$this->message('There are '.$count.' products to be compiled.');
		$index = 0;	
		foreach (array_chunk($ids, 10) as $batch)
		{
			$processHandle = popen('php ' . $batchPath . ' '. implode(' ', $batch), 'r');
			while ($string = fread($processHandle, 1024))
			{
				echo $string;
			}
			pclose($processHandle);
			$index = $index + count($batch);
			echo "Compiling products: $index \n";
		}
		
		$this->quitOk("All products are compiled successfully.");
	}
}