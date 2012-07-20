<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_compiledcrossitem
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_compiledcrossitem extends catalog_persistentdocument_compiledcrossitembase 
{
	/**
	 * @return catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct|null
	 */
	public function getTargetIdInstance()
	{
		return DocumentHelper::getDocumentInstanceIfExists($this->getTargetId());
	}
	
	/**
	 * @return catalog_persistentdocument_product|catalog_persistentdocument_declinedproduct|null
	 */
	public function getLinkedIdInstance()
	{
		return DocumentHelper::getDocumentInstanceIfExists($this->getLinkedId());
	}
}