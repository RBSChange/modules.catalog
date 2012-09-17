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
	 * @return string
	 */
	function getAvailability();
	
	/**
	 * @return boolean
	 */
	function mustSendStockAlert();
}