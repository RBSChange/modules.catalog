<?php
/**
 * catalog_StockableDocument
 * @package modules.catalog
 */
interface catalog_StockableDocument
{			
	/**
	 * @return double | null
	 */
	function getCurrentStockQuantity();
	
	/**
	 * @return string
	 */
	function getCurrentStockLevel();
	
	/**
	 * @return boolean
	 */
	function mustSendStockAlert();
	
}