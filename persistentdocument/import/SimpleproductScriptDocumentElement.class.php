<?php
/**
 * catalog_SimpleproductScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_SimpleproductScriptDocumentElement extends catalog_ProductScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_simpleproduct
	 */
	protected function initPersistentDocument()
	{
		return catalog_SimpleproductService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/simpleproduct');
	}
	
	/**
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		$properties = parent::getDocumentProperties();
		if (isset($properties['attributes']))
		{
			$attributesToSerialize = array();
			$attributes = explode(',', $this->replaceRefIdInString($properties['attributes']));
			foreach ($attributes as $attribute)
			{
				list ($key, $value) = explode(':', $attribute);
				$attributesToSerialize[$key] = $value;
			}
			$properties['attributesJSON'] = json_encode($attributesToSerialize);
			unset($properties['attributes']);
		}
		return $properties;
	}
}