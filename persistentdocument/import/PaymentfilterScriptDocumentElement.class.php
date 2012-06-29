<?php
/**
 * catalog_PaymentfilterScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_PaymentfilterScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_paymentfilter
	 */
	protected function initPersistentDocument()
	{
		return catalog_PaymentfilterService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/paymentfilter');
	}
	
	/**
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		$properties = parent::getDocumentProperties();
		if (isset($properties['query']))
		{
			$query = $this->replaceRefIdInString($properties['query']) ;
			$properties['query'] = $query;
		}
		return $properties;
	}
}