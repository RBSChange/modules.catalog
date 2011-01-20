<?php
/**
 * catalog_DeclinableProduct
 * @package modules.catalog
 */
interface catalog_DeclinableProduct
{			
	/**
	 * @return catalog_persistentdocument_product[]
	 */
	function getDeclinations();
	
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */	
	function getDeclinedproduct();
}