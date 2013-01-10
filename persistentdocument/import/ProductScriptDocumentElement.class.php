<?php
/**
 * catalog_ProductScriptDocumentElement
 * @package modules.catalog
 * @method getPersistentDocument() catalog_persistentdocument_product
 */
class catalog_ProductScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function initPersistentDocument()
	{
		throw new Exception('Can\'t create directly a product, use descendant models (simpleproduct, declinedproduct...');
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
	
	/**
	 * @param import_ScriptExecuteElement $scriptExecute
	 * @param array $attr
	 */
	public function compile($scriptExecute, $attr = null)
	{
		catalog_CompiledproductService::getInstance()->generateForProduct($this->getPersistentDocument());
	}
}