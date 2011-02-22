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
			
		$ids = catalog_ProductService::getInstance()->getAllProductIdsToCompile();
		$count = count($ids);			
		$this->message('There are '.$count.' products to be compiled.');
		$index = 0;	
		foreach (array_chunk($ids, 100) as $chunk)
		{
			$result = f_util_System::execHTTPScript($batchPath, $chunk);
			// Log fatal errors...
			if ($result != 'OK')
			{
				Framework::error(__METHOD__ . ' ' . $batchPath . ' unexpected result: "' . $result . '"');
			}
			$index = $index + count($chunk);
			echo "Compiling products: $index \n";
		}
		
		$this->quitOk("All products are compiled successfully.");
	}
}