<?php
/**
 * catalog_DeclinedproductScriptDocumentElement
 * @package modules.catalog.persistentdocument.import
 */
class catalog_DeclinedproductScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return catalog_persistentdocument_declinedproduct
	 */
	protected function initPersistentDocument()
	{
		$product = catalog_DeclinedproductService::getInstance()->getNewDocumentInstance();
		return $product;
	}

	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_catalog/declinedproduct');
	}
	
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
					echo $shelfCodeRef . "\n";				
				}
				else 
				{
					$properties['shelf'][] = $shelf;
				}
				unset($properties['shelfCodeReferences']);
			}
		}
		if (isset($properties['brandCodeReference']) && f_util_StringUtils::isNotEmpty($properties['brandCodeReference']))
		{
			$brandCodeRef = trim($properties['brandCodeReference']);
			$brand = brand_BrandService::getInstance()->createQuery()->add(Restrictions::eq('codeReference', $brandCodeRef))->findUnique();
			if ($brand === null)
			{
				echo $brandCodeRef . "\n";				
			}
			else 
			{
				$properties['brand'] = $brand;
			}
			unset($properties['brandCodeReference']);
		}
		return $properties;
	}


}