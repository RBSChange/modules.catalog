<?php
/**
 * catalog_StockableDocument
 * @package modules.catalog
 */
interface catalog_StockableDocument
{
	/**
	 * @param String $level
	 */
	function setStockLevel($level);
	
	/**
	 * @return String
	 */
	function getStockLevel();
	
	/**
	 * @return String
	 */
	function getStockLevelOldValue();
	
	/**
	 * @param Double $quantity
	 */
	function setStockQuantity($quantity);
	
	/**
	 * @return String
	 */
	function getStockQuantity();
		
	/**
	 * @return String
	 */
	function getStockQuantityOldValue();
	
	/**
	 * @param Double $threshold
	 */
	function setStockAlertThreshold($threshold);
	
	/**
	 * @return Double
	 */
	function getStockAlertThreshold();
}