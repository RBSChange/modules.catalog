<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_virtualproduct
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_virtualproduct extends catalog_persistentdocument_virtualproductbase implements catalog_StockableDocument
{
	/**
	 * @return String
	 */
	public function getDetailBlockName()
	{
		return 'virtualproduct';
	}	
	
	/**
	 * @return boolean
	 */
	public function updateCartQuantity()
	{
		return false;
	}
}