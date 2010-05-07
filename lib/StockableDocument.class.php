<?php
/**
 * catalog_StockableDocument
 * @package modules.catalog
 */
interface catalog_StockableDocument
{			
	/**
	 * @return Double
	 */
	function getStockQuantity();
		
	/**
	 * >0 increase Quantity
	 * <0 decrease Quantity
	 * @param Double $quantity
	 * @return Double new quantity
	 */
	function addStockQuantity($quantity);
	
	/**
	 * @return boolean
	 */
	function mustSendStockAlert();
	
	/**
	 * @return String
	 */
	function getStockLevel();

}