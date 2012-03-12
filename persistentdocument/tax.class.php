<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_tax
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_tax extends catalog_persistentdocument_taxbase 
{
	/**
	 * @deprecated
	 */
	public function getCode()
	{
		return $this->getTaxZone() . ',' . $this->getTaxCategory();
	}
}