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
		$cps = catalog_CompiledproductService::getInstance();
		$productArray = catalog_ProductService::getInstance()->createQuery()->find();
		$count = count($productArray);
		$index = 0;
		$this->message('There are '.$count.' products to be compiled.');
		$rqc = RequestContext::getInstance();
		$rqc->setLang($rqc->getDefaultLang());
		foreach ($productArray as $product)
		{
			$cps->generateForProduct($product);
			$percent = round((++$index / $count) * 100);
			echo "Compiling products: ", $percent, "% (mem: ", f_util_MemoryUtils::getLoad(true), "%)  \r";
		}
		
		$this->quitOk("All products are compiled successfully.");
	}
}