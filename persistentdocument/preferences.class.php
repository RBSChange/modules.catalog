<?php
/**
 * catalog_persistentdocument_preferences
 * @package modules.catalog
 */
class catalog_persistentdocument_preferences extends catalog_persistentdocument_preferencesbase
{
	/**
	 * @see f_persistentdocument_PersistentDocumentImpl::getLabel()
	 *
	 * @return String
	 */
	public function getLabel()
	{
		return f_Locale::translateUI(parent::getLabel());
	}	
}