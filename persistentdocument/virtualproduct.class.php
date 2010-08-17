<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_virtualproduct
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_virtualproduct extends catalog_persistentdocument_virtualproductbase implements catalog_StockableDocument
{
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

 
	/**
	 * @return String
	 */
	public function getDetailBlockName()
	{
		return 'virtualproduct';
	}	
	
	//catalog_StockableDocument
	
	/**
	 * @return boolean
	 */
	public function updateCartQuantity()
	{
		return false;
	}	
	
	/**
	 * @see catalog_StockableDocument::getStockLevel()
	 * @return catalog_StockService::LEVEL_AVAILABLE
	 */
	public function getStockLevel()
	{
		return catalog_StockService::LEVEL_AVAILABLE;	
	}

	/**
	 * @see catalog_StockableDocument::getStockQuantity()
	 * @return 1
	 */
	public function getStockQuantity()
	{
		return 1;
	}

	/**
	 * @see catalog_StockableDocument::addStockQuantity()
	 * @param Double $quantity
	 * @return 1
	 */
	function addStockQuantity($quantity)
	{	
		return 1;
	}
	
	/**
	 * @see catalog_StockableDocument::mustSendStockAlert()
	 * @return false
	 */
	function mustSendStockAlert()
	{
		return false;
	}
}