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
	 * >0 increase Quantity
	 * <0 decrease Quantity
	 * @param double $quantity
	 * @return double | null new quantity
	 */
	function addStockQuantity($quantity);
	
	/**
	 * @return boolean
	 */
	function mustSendStockAlert();
	
}