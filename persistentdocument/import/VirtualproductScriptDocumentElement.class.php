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
	
	/**
	 * @return array
	 */	
	protected function getDocumentProperties()
	{
		$properties = parent::getDocumentProperties();
		if (isset($properties['shelfCodeReferences']) && f_util_StringUtils::isNotEmpty($properties['shelfCodeReferences']))
		{
			$properties['shelf'] = array();
			foreach (explode(',', $properties['shelfCodeReferences']) as $shelfCodeRef)
			{
				$shelf = catalog_ShelfService::getInstance()->createQuery()->add(Restrictions::eq('codeReference', $shelfCodeRef))->findUnique();
				if ($shelf === null)
				{
					throw new Exception("Shelf Code Reference : $shelfCodeRef not found");				
				}
				$properties['shelf'][] = $shelf;
			}
			unset($properties['shelfCodeReferences']);
		}
		if (isset($properties['brandCodeReference']) && f_util_StringUtils::isNotEmpty($properties['brandCodeReference']))
		{
			$brandCodeRef = trim($properties['brandCodeReference']);
			$brand = brand_BrandService::getInstance()->createQuery()->add(Restrictions::eq('codeReference', $brandCodeRef))->findUnique();
			if ($brand === null)
			{
				throw new Exception("Brand Code Reference : $brandCodeRef not found");				
			}	
			$properties['brand'] = $brand;
			unset($properties['brandCodeReference']);
		}
		return $properties;
	}
}