<?php
/**
 * catalog_VirtualproductScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_VirtualproductScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_virtualproduct
	 */
	protected function initPersistentDocument()
	{
		$virtualProduct = catalog_VirtualproductService::getInstance()->getNewDocumentInstance();
		if (isset($this->attributes['shippingMode-refid']))
		{
			$virtualProduct->setShippingModeId($this->getComputedAttribute('shippingMode')->getId());
			unset($this->attributes['shippingMode-refid']);
		}
		return $virtualProduct;
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/virtualproduct');
	}
}