<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_noshelfproductfolder
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_noshelfproductfolder extends catalog_persistentdocument_noshelfproductfolderbase 
{
	public function getLabel()
	{
		return f_Locale::translateUI("&modules.catalog.document.noshelfproductfolder.document-name;");
	}
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
//	public function addFormProperties($propertiesNames, &$formProperties)
//	{	
//	}
}