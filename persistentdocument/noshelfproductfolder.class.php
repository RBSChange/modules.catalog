<?php
/**
 * Class where to put your custom methods for document catalog_persistentdocument_noshelfproductfolder
 * @package modules.catalog.persistentdocument
 */
class catalog_persistentdocument_noshelfproductfolder extends catalog_persistentdocument_noshelfproductfolderbase 
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		return LocaleService::getInstance()->trans('m.catalog.document.noshelfproductfolder.document-name', array('ucf'));
	}
}