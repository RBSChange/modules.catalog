<?php
/**
 * catalog_persistentdocument_simpleproduct
 * @package catalog.persistentdocument
 */
class catalog_persistentdocument_simpleproduct extends catalog_persistentdocument_simpleproductbase implements catalog_StockableDocument
{
	/**
	 * @return string
	 */
	public function getDetailBlockName()
	{
		return 'simpleproduct';
	}
}